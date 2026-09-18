<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\MatchStatus;
use App\Enums\MemberStatus;
use App\Models\Article;
use App\Models\GameMatch;
use App\Models\MatchLineup;
use App\Models\Member;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('club.home', [
            'latestResult' => $this->latestResult(),
            'nextMatch' => $this->nextMatch(),
            'articles' => $this->articles(),
            'topPlayers' => $this->topPlayersThisMonth(),
            'topScorers' => $this->topScorersThisMonth(),
            'topMvps' => $this->topMvps(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function latestResult(): ?array
    {
        $match = GameMatch::query()
            ->with(['mvp', 'goals.member', 'lineups.member'])
            ->where('status', MatchStatus::Completed)
            ->latest('kickoff_at')
            ->first();

        return $match ? $this->presentMatch($match) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nextMatch(): ?array
    {
        $match = GameMatch::query()
            ->with(['mvp', 'goals.member'])
            ->withAttendanceCounts()
            ->where('status', MatchStatus::Scheduled)
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at')
            ->first();

        return $match ? $this->presentMatch($match) : null;
    }

    /**
     * @return array<int, array{id: int, title: string, slug: string, excerpt: string|null, category: string, category_label: string, cover: string|null, published_at: string|null}>
     */
    private function articles(): array
    {
        return Article::query()
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (Article $article): array => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'category' => $article->category->value,
                'category_label' => $article->category->label(),
                'cover' => $article->cover_image_path ? asset($article->cover_image_path) : null,
                'published_at' => $article->published_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, jersey_number: int|null, photo: string|null, value: int}>
     */
    private function topPlayersThisMonth(): array
    {
        return Member::query()
            ->where('status', MemberStatus::Active)
            ->withCount(['attendances as matches_played' => function (Builder $query): void {
                $query->where('status', AttendanceStatus::Attending)
                    ->whereHas('match', function (Builder $matchQuery): void {
                        $matchQuery->where('status', MatchStatus::Completed)
                            ->whereBetween('kickoff_at', [now()->startOfMonth(), now()->endOfMonth()]);
                    });
            }])
            ->orderByDesc('matches_played')
            ->limit(5)
            ->get()
            ->filter(fn (Member $member): bool => $member->matches_played > 0)
            ->values()
            ->map(fn (Member $member): array => $this->playerStat($member, (int) $member->matches_played))
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, jersey_number: int|null, photo: string|null, value: int|string}>
     */
    private function topScorersThisMonth(): array
    {
        return Member::query()
            ->where('status', MemberStatus::Active)
            ->withCount(['goals as goals_count' => function (Builder $query): void {
                $query->whereHas('match', function (Builder $matchQuery): void {
                    $matchQuery->where('status', MatchStatus::Completed)
                        ->whereBetween('kickoff_at', [now()->startOfMonth(), now()->endOfMonth()]);
                });
            }])
            ->orderByDesc('goals_count')
            ->limit(5)
            ->get()
            ->filter(fn (Member $member): bool => $member->goals_count > 0)
            ->values()
            ->map(fn (Member $member): array => $this->playerStat($member, (int) $member->goals_count))
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, jersey_number: int|null, photo: string|null, value: int|string}>
     */
    private function topMvps(): array
    {
        $matches = GameMatch::query()
            ->where('status', MatchStatus::Completed)
            ->with(['lineups.member', 'goals'])
            ->get();

        $totals = [];

        foreach ($matches as $match) {
            foreach ($match->lineups as $lineup) {
                if ($lineup->rating === null || $lineup->member?->status !== MemberStatus::Active) {
                    continue;
                }

                $memberId = $lineup->member_id;
                $totals[$memberId]['sum'] = ($totals[$memberId]['sum'] ?? 0) + (float) $lineup->rating;
                $totals[$memberId]['count'] = ($totals[$memberId]['count'] ?? 0) + 1;
                $totals[$memberId]['goals'] = $totals[$memberId]['goals'] ?? 0;
                $totals[$memberId]['member'] = $lineup->member;
            }

            foreach ($match->goals as $goal) {
                if (! isset($totals[$goal->member_id])) {
                    continue;
                }

                $totals[$goal->member_id]['goals']++;
            }
        }

        if ($totals === []) {
            return [];
        }

        uasort($totals, function (array $left, array $right): int {
            $leftAvg = $left['sum'] / $left['count'];
            $rightAvg = $right['sum'] / $right['count'];
            $avgCompare = $rightAvg <=> $leftAvg;

            if ($avgCompare !== 0) {
                return $avgCompare;
            }

            $goalCompare = $right['goals'] <=> $left['goals'];

            if ($goalCompare !== 0) {
                return $goalCompare;
            }

            return $right['count'] <=> $left['count'];
        });

        return collect(array_slice($totals, 0, 5, true))
            ->map(fn (array $row): array => $this->playerStat(
                $row['member'],
                $row['sum'] / $row['count'],
                1,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMatch(GameMatch $match): array
    {
        $scorers = $match->goals
            ->groupBy('member_id')
            ->map(function ($goals): ?array {
                $member = $goals->first()?->member;

                if (! $member) {
                    return null;
                }

                return [
                    'name' => $member->displayName(),
                    'count' => $goals->count(),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $scorersLabel = collect($scorers)
            ->map(fn (array $scorer): string => $scorer['count'] > 1
                ? $scorer['name'].' ('.$scorer['count'].')'
                : $scorer['name'])
            ->implode(', ');

        $mvpName = $match->mvp?->displayName();

        if (! $mvpName) {
            $inferredId = $this->inferredMvpId($match);
            $mvpName = $inferredId
                ? $match->lineups->firstWhere('member_id', $inferredId)?->member?->displayName()
                : null;
        }

        return [
            'id' => $match->id,
            'title' => $match->displayTitle(),
            'opponent' => $match->opponent,
            'venue' => $match->venue,
            'is_home' => $match->is_home,
            'kickoff_at' => $match->kickoff_at->toIso8601String(),
            'status' => $match->status->value,
            'status_label' => $match->status->label(),
            'score_for' => $match->score_for,
            'score_against' => $match->score_against,
            'mvp_name' => $mvpName,
            'scorers' => $scorers,
            'scorers_label' => $scorersLabel,
            'weekday_label' => $this->weekdayLabel($match->kickoff_at),
            'attending_count' => (int) ($match->attending_count ?? 0),
            'absent_count' => (int) ($match->absent_count ?? 0),
            'pending_count' => (int) ($match->pending_count ?? 0),
        ];
    }

    private function weekdayLabel(CarbonInterface $kickoff): string
    {
        return match ($kickoff->timezone(config('app.timezone'))->dayOfWeek) {
            0 => 'Chủ Nhật',
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
        };
    }

    private function inferredMvpId(GameMatch $match): ?int
    {
        if ($match->lineups->isEmpty()) {
            return null;
        }

        $goalCounts = $match->goals->groupBy('member_id')->map->count();

        $best = $match->lineups
            ->filter(fn (MatchLineup $lineup): bool => $lineup->rating !== null)
            ->sort(function (MatchLineup $left, MatchLineup $right) use ($goalCounts): int {
                $ratingCompare = ((float) $right->rating) <=> ((float) $left->rating);

                if ($ratingCompare !== 0) {
                    return $ratingCompare;
                }

                return ((int) ($goalCounts[$right->member_id] ?? 0))
                    <=> ((int) ($goalCounts[$left->member_id] ?? 0));
            })
            ->first();

        return $best?->member_id;
    }

    /**
     * @return array{id: int, name: string, jersey_number: int|null, photo: string|null, value: int|string}
     */
    private function playerStat(Member $member, int|float $value, int $decimals = 0): array
    {
        return [
            'id' => $member->id,
            'name' => $member->displayName(),
            'jersey_number' => $member->jersey_number,
            'photo' => $member->photoUrl(),
            'value' => $decimals > 0
                ? number_format((float) $value, $decimals)
                : (int) $value,
        ];
    }
}

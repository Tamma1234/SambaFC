<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\MatchStatus;
use App\Enums\MemberStatus;
use App\Models\Article;
use App\Models\GameMatch;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('club.home', [
            'nextMatch' => $this->nextMatch(),
            'articles' => $this->articles(),
            'topPlayers' => $this->topPlayersThisMonth(),
            'topMvps' => $this->topMvps(),
        ]);
    }

    /**
     * @return array{id: int, title: string, opponent: string, venue: string|null, is_home: bool, kickoff_at: string}|null
     */
    private function nextMatch(): ?array
    {
        $match = GameMatch::query()
            ->where('status', MatchStatus::Scheduled)
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at')
            ->first();

        if (! $match) {
            return null;
        }

        return [
            'id' => $match->id,
            'title' => $match->displayTitle(),
            'opponent' => $match->opponent,
            'venue' => $match->venue,
            'is_home' => $match->is_home,
            'kickoff_at' => $match->kickoff_at->toIso8601String(),
        ];
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
     * @return array<int, array{id: int, name: string, jersey_number: int|null, photo: string|null, value: int}>
     */
    private function topMvps(): array
    {
        return Member::query()
            ->where('status', MemberStatus::Active)
            ->withCount(['mvpMatches as mvp_count' => function (Builder $query): void {
                $query->where('status', MatchStatus::Completed);
            }])
            ->orderByDesc('mvp_count')
            ->limit(5)
            ->get()
            ->filter(fn (Member $member): bool => $member->mvp_count > 0)
            ->values()
            ->map(fn (Member $member): array => $this->playerStat($member, (int) $member->mvp_count))
            ->all();
    }

    /**
     * @return array{id: int, name: string, jersey_number: int|null, photo: string|null, value: int}
     */
    private function playerStat(Member $member, int $value): array
    {
        return [
            'id' => $member->id,
            'name' => $member->displayName(),
            'jersey_number' => $member->jersey_number,
            'photo' => $member->photoUrl(),
            'value' => $value,
        ];
    }
}

<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\FundStatus;
use App\Enums\MatchStatus;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PitchSlot;
use App\Models\FundContribution;
use App\Models\GameMatch;
use App\Models\MatchGoal;
use App\Models\MatchLineup;
use App\Models\Member;
use Illuminate\Support\Collection;

final class ClubAdminPresenter
{
    /**
     * @return array{
     *     members_total: int,
     *     members_active: int,
     *     upcoming_matches: int,
     *     matches_this_month: int,
     *     pending_attendance: int
     * }
     */
    public static function dashboardStats(): array
    {
        return [
            'members_total' => Member::query()->count(),
            'members_active' => Member::query()->where('status', MemberStatus::Active)->count(),
            'upcoming_matches' => GameMatch::query()
                ->where('status', MatchStatus::Scheduled)
                ->where('kickoff_at', '>=', now())
                ->count(),
            'matches_this_month' => GameMatch::query()
                ->where('status', MatchStatus::Completed)
                ->whereBetween('kickoff_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'pending_attendance' => GameMatch::query()
                ->where('status', MatchStatus::Scheduled)
                ->where('kickoff_at', '>=', now())
                ->withCount([
                    'attendances as pending_count' => fn ($query) => $query->where('status', AttendanceStatus::Pending),
                ])
                ->get()
                ->sum('pending_count'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function member(Member $member): array
    {
        $position = $member->preferred_position
            ? PitchSlot::tryFrom($member->preferred_position)
            : null;

        return [
            'id' => $member->id,
            'name' => $member->name,
            'nickname' => $member->nickname,
            'display_name' => $member->displayName(),
            'jersey_number' => $member->jersey_number,
            'preferred_position' => $member->preferred_position,
            'preferred_position_label' => $position?->label(),
            'photo_path' => $member->photo_path,
            'photo' => $member->photoUrl(),
            'phone' => $member->phone,
            'date_of_birth' => $member->date_of_birth?->toDateString(),
            'joined_at' => $member->joined_at?->toDateString(),
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
            'role' => $member->role->value,
            'role_label' => $member->role->label(),
            'bio' => $member->bio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function match(GameMatch $match): array
    {
        return [
            'id' => $match->id,
            'title' => $match->title,
            'display_title' => $match->displayTitle(),
            'opponent' => $match->opponent,
            'venue' => $match->venue,
            'is_home' => $match->is_home,
            'kickoff_at' => $match->kickoff_at->toIso8601String(),
            'kickoff_input' => $match->kickoff_at->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'score_for' => $match->score_for,
            'score_against' => $match->score_against,
            'status' => $match->status->value,
            'status_label' => $match->status->label(),
            'mvp_member_id' => $match->mvp_member_id,
            'mvp_name' => $match->mvp?->displayName(),
            'notes' => $match->notes,
            'attending_count' => (int) ($match->attending_count ?? 0),
            'absent_count' => (int) ($match->absent_count ?? 0),
            'pending_count' => (int) ($match->pending_count ?? 0),
        ];
    }

    /**
     * @param  Collection<int, Member>  $members
     * @return array<int, array<string, mixed>>
     */
    public static function members(Collection $members): array
    {
        return $members->map(fn (Member $member): array => self::member($member))->all();
    }

    /**
     * @param  Collection<int, GameMatch>  $matches
     * @return array<int, array<string, mixed>>
     */
    public static function matches(Collection $matches): array
    {
        return $matches->map(fn (GameMatch $match): array => self::match($match))->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function positions(): array
    {
        return self::enumOptions(PitchSlot::starters());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function memberStatuses(): array
    {
        return self::enumOptions(MemberStatus::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function memberRoles(): array
    {
        return self::enumOptions(MemberRole::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function matchStatuses(): array
    {
        return self::enumOptions(MatchStatus::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function attendanceStatuses(): array
    {
        return self::enumOptions(AttendanceStatus::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function fundStatuses(): array
    {
        return self::enumOptions(FundStatus::cases());
    }

    /**
     * @return array<string, mixed>
     */
    public static function lineup(MatchLineup $lineup): array
    {
        return [
            'id' => $lineup->id,
            'member_id' => $lineup->member_id,
            'member_name' => $lineup->member?->displayName(),
            'photo' => $lineup->member?->photoUrl(),
            'jersey_number' => $lineup->member?->jersey_number,
            'slot' => $lineup->slot->value,
            'slot_label' => $lineup->slot->label(),
            'is_starter' => $lineup->slot->isStarter(),
            'rating' => $lineup->rating !== null ? (float) $lineup->rating : null,
            'top' => $lineup->slot->pitchTop(),
            'left' => $lineup->slot->pitchLeft(),
        ];
    }

    /**
     * @param  Collection<int, MatchLineup>  $lineups
     * @return array<int, array<string, mixed>>
     */
    public static function lineups(Collection $lineups): array
    {
        return $lineups
            ->sortBy(fn (MatchLineup $lineup): int => $lineup->sort_order)
            ->values()
            ->map(fn (MatchLineup $lineup): array => self::lineup($lineup))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function goal(MatchGoal $goal): array
    {
        return [
            'id' => $goal->id,
            'member_id' => $goal->member_id,
            'member_name' => $goal->member?->displayName(),
            'minute' => $goal->minute,
        ];
    }

    /**
     * @param  Collection<int, MatchGoal>  $goals
     * @return array<int, array<string, mixed>>
     */
    public static function goals(Collection $goals): array
    {
        return $goals
            ->values()
            ->map(fn (MatchGoal $goal): array => self::goal($goal))
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, group_label: string, is_starter: bool, top: string|null, left: string|null}>
     */
    public static function pitchSlots(): array
    {
        return array_map(fn (PitchSlot $slot): array => [
            'value' => $slot->value,
            'label' => $slot->label(),
            'group_label' => $slot->groupLabel(),
            'is_starter' => $slot->isStarter(),
            'top' => $slot->pitchTop(),
            'left' => $slot->pitchLeft(),
        ], [...PitchSlot::starters(), ...PitchSlot::substitutes()]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fund(FundContribution $contribution): array
    {
        return [
            'id' => $contribution->id,
            'group_key' => $contribution->group_key,
            'member_id' => $contribution->member_id,
            'member_name' => $contribution->member?->displayName(),
            'title' => $contribution->title,
            'amount' => $contribution->amount,
            'status' => $contribution->status->value,
            'status_label' => $contribution->status->label(),
            'paid_at' => $contribution->paid_at?->toIso8601String(),
            'note' => $contribution->note,
        ];
    }

    /**
     * @param  Collection<int, FundContribution>  $contributions
     * @return array<int, array<string, mixed>>
     */
    public static function funds(Collection $contributions): array
    {
        return $contributions->map(fn (FundContribution $contribution): array => self::fund($contribution))->all();
    }

    /**
     * @param  Collection<int, FundContribution>  $contributions
     * @return array<int, array<string, mixed>>
     */
    public static function fundGroups(Collection $contributions): array
    {
        return $contributions
            ->groupBy(fn (FundContribution $contribution): string => $contribution->group_key ?: 'single-'.$contribution->id)
            ->map(function (Collection $rows): array {
                /** @var FundContribution $first */
                $first = $rows->first();
                $ordered = $rows
                    ->sortBy(fn (FundContribution $contribution): string => $contribution->member?->displayName() ?? '')
                    ->values();

                return [
                    'group_key' => $first->group_key,
                    'title' => $first->title ?: 'Quỹ đội',
                    'amount' => $first->amount,
                    'paid_count' => $rows->where('status', FundStatus::Paid)->count(),
                    'unpaid_count' => $rows->where('status', FundStatus::Unpaid)->count(),
                    'contributions' => self::funds($ordered),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{paid_total: int, unpaid_total: int, paid_count: int, unpaid_count: int}
     */
    public static function fundSummary(): array
    {
        return [
            'paid_total' => (int) FundContribution::query()->where('status', FundStatus::Paid)->sum('amount'),
            'unpaid_total' => (int) FundContribution::query()->where('status', FundStatus::Unpaid)->sum('amount'),
            'paid_count' => FundContribution::query()->where('status', FundStatus::Paid)->count(),
            'unpaid_count' => FundContribution::query()->where('status', FundStatus::Unpaid)->count(),
        ];
    }

    /**
     * @param  array<int, \BackedEnum&object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private static function enumOptions(array $cases): array
    {
        return array_map(fn ($case): array => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
        ], $cases);
    }
}

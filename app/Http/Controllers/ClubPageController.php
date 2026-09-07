<?php

namespace App\Http\Controllers;

use App\Enums\MemberStatus;
use App\Enums\PitchSlot;
use App\Models\Member;
use Illuminate\View\View;

class ClubPageController extends Controller
{
    public function news(): View
    {
        return view('club.news');
    }

    public function members(): View
    {
        return view('club.members', [
            'members' => $this->publicMembers(),
        ]);
    }

    public function stats(): View
    {
        return view('club.stats');
    }

    /**
     * @return array<int, array{id: int, nickname: string, position: string, photo: string|null}>
     */
    private function publicMembers(): array
    {
        return Member::query()
            ->with('user')
            ->where('status', MemberStatus::Active)
            ->orderByRaw('jersey_number is null')
            ->orderBy('jersey_number')
            ->orderBy('name')
            ->get()
            ->map(function (Member $member): array {
                $slot = $member->preferred_position
                    ? PitchSlot::tryFrom($member->preferred_position)
                    : null;

                return [
                    'id' => $member->id,
                    'nickname' => $member->displayName(),
                    'position' => $slot?->groupLabel() ?? 'Cầu Thủ',
                    'photo' => $member->photoUrl(),
                ];
            })
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Club;

use App\Enums\AttendanceStatus;
use App\Enums\PitchSlot;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\SaveMatchRequest;
use App\Http\Requests\Club\SaveMatchStatsRequest;
use App\Models\GameMatch;
use App\Models\MatchGoal;
use App\Models\MatchLineup;
use App\Models\Member;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function index(): Response
    {
        $matches = GameMatch::query()
            ->with('mvp')
            ->withAttendanceCounts()
            ->latest('kickoff_at')
            ->get();

        $members = Member::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('club/matches/index', [
            'matches' => ClubAdminPresenter::matches($matches),
            'members' => ClubAdminPresenter::members($members),
            'statuses' => ClubAdminPresenter::matchStatuses(),
        ]);
    }

    public function show(string $current_team, GameMatch $gameMatch): Response
    {
        $gameMatch->load(['mvp', 'lineups.member', 'goals.member']);
        $gameMatch->loadCount([
            'attendances as attending_count' => fn ($query) => $query->where('status', AttendanceStatus::Attending),
            'attendances as absent_count' => fn ($query) => $query->where('status', AttendanceStatus::Absent),
            'attendances as pending_count' => fn ($query) => $query->where('status', AttendanceStatus::Pending),
        ]);

        $members = Member::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('club/matches/show', [
            'match' => ClubAdminPresenter::match($gameMatch),
            'lineups' => ClubAdminPresenter::lineups($gameMatch->lineups),
            'goals' => ClubAdminPresenter::goals($gameMatch->goals),
            'members' => ClubAdminPresenter::members($members),
            'slots' => ClubAdminPresenter::pitchSlots(),
        ]);
    }

    public function store(SaveMatchRequest $request): RedirectResponse
    {
        $match = GameMatch::query()->create($request->validated());
        $match->syncRosterAttendances();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã tạo trận đấu.']);

        return back();
    }

    public function update(SaveMatchRequest $request, string $current_team, GameMatch $gameMatch): RedirectResponse
    {
        $gameMatch->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã cập nhật trận đấu.']);

        return back();
    }

    public function updateStats(SaveMatchStatsRequest $request, string $current_team, GameMatch $gameMatch): RedirectResponse
    {
        $gameMatch->lineups()->delete();
        $gameMatch->goals()->delete();

        foreach (array_values($request->validated('lineups')) as $index => $row) {
            if (blank($row['member_id'] ?? null)) {
                continue;
            }

            $slot = PitchSlot::from($row['slot']);

            MatchLineup::query()->create([
                'match_id' => $gameMatch->id,
                'member_id' => $row['member_id'],
                'slot' => $slot,
                'is_starter' => $slot->isStarter(),
                'rating' => $row['rating'] ?? null,
                'sort_order' => $index,
            ]);
        }

        foreach (array_values($request->validated('goals')) as $index => $row) {
            MatchGoal::query()->create([
                'match_id' => $gameMatch->id,
                'member_id' => $row['member_id'],
                'minute' => $row['minute'] ?? null,
                'sort_order' => $index,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã lưu thống kê trận đấu.']);

        return back();
    }

    public function destroy(string $current_team, GameMatch $gameMatch): RedirectResponse
    {
        $gameMatch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã xóa trận đấu.']);

        return back();
    }
}

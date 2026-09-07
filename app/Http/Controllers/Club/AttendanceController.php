<?php

namespace App\Http\Controllers\Club;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\SaveAttendanceRequest;
use App\Models\GameMatch;
use App\Models\MatchAttendance;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(): Response
    {
        $matches = GameMatch::query()
            ->withAttendanceCounts()
            ->latest('kickoff_at')
            ->get();

        return Inertia::render('club/attendances/index', [
            'matches' => ClubAdminPresenter::matches($matches),
        ]);
    }

    public function edit(string $current_team, GameMatch $gameMatch): Response
    {
        $gameMatch->syncRosterAttendances();
        $gameMatch->load(['attendances.member']);
        $gameMatch->loadCount([
            'attendances as attending_count' => fn ($query) => $query->where('status', AttendanceStatus::Attending),
            'attendances as absent_count' => fn ($query) => $query->where('status', AttendanceStatus::Absent),
            'attendances as pending_count' => fn ($query) => $query->where('status', AttendanceStatus::Pending),
        ]);

        $attendances = $gameMatch->attendances
            ->sortBy(fn (MatchAttendance $attendance): string => $attendance->member->displayName())
            ->values()
            ->map(fn (MatchAttendance $attendance): array => [
                'id' => $attendance->id,
                'member_id' => $attendance->member_id,
                'member_name' => $attendance->member->displayName(),
                'jersey_number' => $attendance->member->jersey_number,
                'status' => $attendance->status->value,
                'note' => $attendance->note,
            ])
            ->all();

        return Inertia::render('club/attendances/edit', [
            'match' => ClubAdminPresenter::match($gameMatch),
            'attendances' => $attendances,
            'statuses' => ClubAdminPresenter::attendanceStatuses(),
        ]);
    }

    public function update(SaveAttendanceRequest $request, string $current_team, GameMatch $gameMatch): RedirectResponse
    {
        foreach ($request->validated('attendances') as $row) {
            MatchAttendance::query()->updateOrCreate(
                [
                    'match_id' => $gameMatch->id,
                    'member_id' => $row['member_id'],
                ],
                [
                    'status' => $row['status'],
                    'note' => $row['note'] ?? null,
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Đã lưu điểm danh.']);

        return back();
    }
}

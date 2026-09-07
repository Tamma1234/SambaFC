<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\TeamInvitation;
use App\Support\ClubAdminPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $upcoming = GameMatch::query()
            ->with('mvp')
            ->withAttendanceCounts()
            ->where('status', MatchStatus::Scheduled)
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at')
            ->limit(5)
            ->get();

        $recent = GameMatch::query()
            ->with('mvp')
            ->withAttendanceCounts()
            ->where('status', MatchStatus::Completed)
            ->latest('kickoff_at')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'stats' => ClubAdminPresenter::dashboardStats(),
            'upcomingMatches' => ClubAdminPresenter::matches($upcoming),
            'recentMatches' => ClubAdminPresenter::matches($recent),
        ]);
    }
}

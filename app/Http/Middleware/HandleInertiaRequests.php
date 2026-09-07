<?php

namespace App\Http\Middleware;

use App\Models\ClubSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'club' => function () {
                $club = ClubSetting::current();

                return [
                    'name' => $club->name,
                    'logo' => $club->logoUrl(),
                    'cover' => $club->coverUrl(),
                    'description' => $club->description,
                    'home_venue' => $club->home_venue,
                    'founded_year' => $club->founded_year,
                    'facebook_url' => $club->facebook_url,
                    'contact_email' => $club->contact_email,
                ];
            },
        ];
    }
}

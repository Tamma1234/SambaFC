<?php

namespace App\Actions\Auth;

use App\Actions\Teams\CreateTeam;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class FindOrCreateGoogleUser
{
    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    public function handle(SocialiteUser $googleUser): User
    {
        $email = $googleUser->getEmail();

        abort_unless(is_string($email) && $email !== '', 422, 'Tài khoản Google không có email.');

        return DB::transaction(function () use ($googleUser, $email) {
            $user = User::query()->where('google_id', $googleUser->getId())->first()
                ?? User::query()->where('email', $email)->first();

            if ($user) {
                $this->syncExistingUser($user, $googleUser);

                return $user;
            }

            return $this->createUser($googleUser, $email);
        });
    }

    private function syncExistingUser(User $user, SocialiteUser $googleUser): void
    {
        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar() ?: $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->ensureWorkspace($user);
        $this->ensureMember($user, $googleUser->getAvatar());
    }

    private function createUser(SocialiteUser $googleUser, string $email): User
    {
        $isFirstUser = ! User::query()->exists();

        $user = User::query()->create([
            'name' => $googleUser->getName() ?: 'Cầu thủ Samba',
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => null,
            'email_verified_at' => now(),
            'role' => $isFirstUser ? UserRole::Admin : UserRole::Member,
        ]);

        $this->ensureWorkspace($user);
        $this->ensureMember($user, $googleUser->getAvatar());

        return $user;
    }

    private function ensureWorkspace(User $user): void
    {
        if ($user->currentTeam || $user->personalTeam()) {
            return;
        }

        $this->createTeam->handle($user, 'Samba FC', isPersonal: true);
    }

    private function ensureMember(User $user, ?string $avatar): void
    {
        if ($user->member()->exists()) {
            return;
        }

        Member::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'photo_path' => $avatar,
            'joined_at' => now()->toDateString(),
            'status' => MemberStatus::Active,
            'role' => MemberRole::Player,
        ]);
    }
}

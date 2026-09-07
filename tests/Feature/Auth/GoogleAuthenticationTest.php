<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_includes_google_button_state(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Đăng nhập với Google')
            ->assertDontSee('GOOGLE_CLIENT_ID');
    }

    public function test_google_redirect_requires_configuration(): void
    {
        config(['services.google.client_id' => null]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'));
    }

    public function test_google_redirect_sends_the_user_to_google(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://accounts.google.com'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com');
    }

    public function test_new_google_user_is_created_and_authenticated(): void
    {
        $this->mockGoogleUser();

        $this->get(route('auth.google.callback'))
            ->assertRedirect();

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'player@samba.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertNotNull($user->currentTeam);
        $this->assertTrue(Member::query()->where('user_id', $user->id)->exists());
    }

    public function test_existing_google_email_is_linked_instead_of_duplicated(): void
    {
        $existing = User::factory()->create([
            'email' => 'player@samba.test',
            'role' => UserRole::Member,
        ]);

        $this->mockGoogleUser();

        $this->get(route('auth.google.callback'))->assertRedirect();

        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame(1, User::query()->where('email', 'player@samba.test')->count());
        $this->assertSame('google-123', $existing->fresh()->google_id);
    }

    public function test_google_only_account_cannot_login_with_password(): void
    {
        $user = User::factory()->create([
            'email' => 'player@samba.test',
        ]);

        User::query()->whereKey($user->id)->update([
            'password' => null,
            'google_id' => 'google-123',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'player@samba.test',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.google_only')]);

        $this->assertGuest();
    }

    private function mockGoogleUser(): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('Cầu thủ Samba');
        $googleUser->shouldReceive('getEmail')->andReturn('player@samba.test');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}

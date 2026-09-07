<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateGoogleUser;
use App\Http\Controllers\Controller;
use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleAuthController extends Controller
{
    use RedirectsToCurrentTeam;

    public function redirect(): RedirectResponse|SymfonyRedirect
    {
        if (! filled(config('services.google.client_id'))) {
            return redirect()
                ->route('login')
                ->with('status', 'Đăng nhập Google chưa được cấu hình. Vui lòng thêm GOOGLE_CLIENT_ID vào file .env.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, FindOrCreateGoogleUser $findOrCreateGoogleUser): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->with('status', 'Đăng nhập Google không thành công. Vui lòng thử lại.');
        }

        $user = $findOrCreateGoogleUser->handle($googleUser);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(
            $this->redirectPathForCurrentTeam($request, Fortify::redirects('login')),
        );
    }
}

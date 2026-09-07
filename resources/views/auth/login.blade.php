@extends('layouts.auth')

@section('title', 'Đăng nhập')

@section('content')
<div class="auth-page">
    <a class="auth-brand" href="{{ route('home') }}">
        <img src="{{ $club->logoUrl() }}" alt="{{ $club->name }}">
        <strong>{{ $club->name }}</strong>
    </a>

    <div class="auth-card">
        <h1>Đăng nhập</h1>
        <p class="auth-lead">Dùng Google hoặc email để vào hệ thống quản lý đội bóng.</p>

        @if (!empty($teamInvitation))
            <div class="auth-info">Bạn được mời vào đội <strong>{{ $teamInvitation['teamName'] }}</strong>. Đăng nhập để tiếp tục.</div>
        @endif

        @if (session('status'))
            <div class="auth-info">{{ session('status') }}</div>
        @endif

        <a class="google-btn" href="{{ route('auth.google.redirect') }}">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1Z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23Z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84Z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53Z" fill="#EA4335"/>
            </svg>
            Đăng nhập với Google
        </a>

        <div class="auth-sep"><span>hoặc email</span></div>

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="email@example.com">
            @error('email')
                <div class="auth-error">{{ $message }}</div>
            @enderror

            <div class="auth-row">
                <label for="password">Mật khẩu</label>
                @if (!empty($canResetPassword))
                    <a href="{{ route('password.request') }}">Quên mật khẩu?</a>
                @endif
            </div>
            <div class="password-wrap">
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Nhập mật khẩu">
                <button type="button" class="toggle-pass" onclick="togglePassword()" aria-label="Hiện mật khẩu">Hiện</button>
            </div>
            @error('password')
                <div class="auth-error">{{ $message }}</div>
            @enderror

            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                Ghi nhớ đăng nhập
            </label>

            <button type="submit" class="submit-btn">Đăng nhập</button>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const btn = document.querySelector('.toggle-pass');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Ẩn' : 'Hiện';
}
</script>
@endsection

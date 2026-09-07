<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $club->name) - {{ $club->name }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/club.css') }}">
</head>
<body class="club-body">
<div class="club-wrap">
    <header class="club-header">
        <div class="club-header-inner">
            <a class="club-brand" href="{{ route('home') }}">
                <img src="{{ $club->logoUrl() }}" alt="{{ $club->name }}">
                <span>{{ $club->name }}</span>
            </a>
            <nav class="club-nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">Trang Chủ</a>
                <a href="{{ route('news.index') }}" class="{{ request()->routeIs('news.*') ? 'is-active' : '' }}">Tin Tức</a>
                <a href="{{ route('members.index') }}" class="{{ request()->routeIs('members.*') ? 'is-active' : '' }}">Thành Viên</a>
                <a href="{{ route('stats.index') }}" class="{{ request()->routeIs('stats.*') ? 'is-active' : '' }}">Thống Kê</a>
                @auth
                    @if (auth()->user()->currentTeam)
                        <a class="login-link" href="{{ route('dashboard', auth()->user()->currentTeam) }}">{{ auth()->user()->name }}</a>
                    @else
                        <a class="login-link" href="{{ route('login') }}">Tài khoản</a>
                    @endif
                @else
                    <a class="login-link" href="{{ route('login') }}">Đăng nhập</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="club-main">
        @yield('content')
    </main>

    <footer class="club-footer">
        <div class="club-footer-inner">
            <div class="club-footer-grid">
                <div>
                    <a class="club-brand" href="{{ route('home') }}">
                        <img src="{{ $club->logoUrl() }}" alt="{{ $club->name }}">
                        <span>{{ $club->name }}</span>
                    </a>
                    <p>{{ $club->description ?: 'Hệ thống quản lý đội bóng: thành viên, tin tức, trận đấu và thống kê.' }}</p>
                </div>
                <div>
                    <h3>Menu</h3>
                    <ul>
                        <li><a href="{{ route('home') }}">Trang Chủ</a></li>
                        <li><a href="{{ route('news.index') }}">Tin Tức</a></li>
                        <li><a href="{{ route('members.index') }}">Thành Viên</a></li>
                        <li><a href="{{ route('stats.index') }}">Thống Kê</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Liên hệ</h3>
                    <ul>
                        @if ($club->home_venue)
                            <li>Sân nhà: {{ $club->home_venue }}</li>
                        @endif
                        @if ($club->contact_email)
                            <li>Email: {{ $club->contact_email }}</li>
                        @endif
                        @if ($club->founded_year)
                            <li>Thành lập: {{ $club->founded_year }}</li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="footer-copy">© {{ date('Y') }} {{ $club->name }}. All rights reserved.</div>
        </div>
    </footer>
</div>
</body>
</html>

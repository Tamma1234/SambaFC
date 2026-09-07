@extends('layouts.club')

@section('title', 'Trang chủ')

@section('content')
    @php
        $cover = $club->coverUrl();
    @endphp

    <section class="hero {{ $cover ? 'has-cover' : '' }}" @if($cover) style="background-image: linear-gradient(180deg, rgba(11,18,32,.25), rgba(11,18,32,.78)), url('{{ $cover }}');" @endif>
        <div>
            <img class="hero-logo" src="{{ $club->logoUrl() }}" alt="{{ $club->name }}">
            <div style="color:#7dffa3; font-weight:700; letter-spacing:2px; font-size:13px;">CÂU LẠC BỘ BÓNG ĐÁ</div>
            <h1>{{ $club->name }}</h1>
            <p>{{ $club->description ?: 'Quản lý thành viên, tin tức, trận đấu và thống kê của đội.' }}</p>
            @if ($club->home_venue)
                <p style="margin-top:8px;">Sân nhà: {{ $club->home_venue }}</p>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Trận đấu tiếp theo</h2>
            <a href="{{ route('stats.index') }}">Xem thống kê</a>
        </div>
        @if ($nextMatch)
            <div class="card">
                <div class="badge">Sắp diễn ra</div>
                <h3 class="match-title">{{ $nextMatch['title'] }}</h3>
                <div class="match-meta">
                    {{ \Illuminate\Support\Carbon::parse($nextMatch['kickoff_at'])->timezone(config('app.timezone'))->format('H:i d/m/Y') }}
                    @if ($nextMatch['venue'])
                        · {{ $nextMatch['venue'] }}
                    @endif
                    · {{ $nextMatch['is_home'] ? 'Sân nhà' : 'Sân khách' }}
                </div>
            </div>
        @else
            <div class="empty">Chưa có trận đấu sắp tới. Khi thêm lịch thi đấu, thông tin sẽ hiện ở đây.</div>
        @endif
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Tin tức</h2>
            <a href="{{ route('news.index') }}">Xem thêm</a>
        </div>
        @if (count($articles))
            <div class="news-grid">
                @foreach ($articles as $article)
                    <a class="card news-card" href="{{ route('news.index') }}">
                        @if ($article['cover'])
                            <img src="{{ $article['cover'] }}" alt="{{ $article['title'] }}">
                        @else
                            <img class="news-thumb" src="{{ $club->logoUrl() }}" alt="">
                        @endif
                        <div class="badge">{{ $article['category_label'] }}</div>
                        <h3>{{ $article['title'] }}</h3>
                        @if ($article['excerpt'])
                            <p>{{ $article['excerpt'] }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty">Chưa có bài viết. Trang tin tức sẽ hiển thị các bài mới của CLB.</div>
        @endif
    </section>

    <section class="stat-grid section">
        <div>
            <div class="section-head"><h2>Top 5 đá nhiều nhất tháng</h2></div>
            @include('club.partials.player-list', ['players' => $topPlayers, 'unit' => 'trận', 'empty' => 'Chưa có dữ liệu tham gia trận trong tháng này.'])
        </div>
        <div>
            <div class="section-head"><h2>Top 5 MVP</h2></div>
            @include('club.partials.player-list', ['players' => $topMvps, 'unit' => 'MVP', 'empty' => 'Chưa có cầu thủ được chọn MVP.'])
        </div>
    </section>
@endsection

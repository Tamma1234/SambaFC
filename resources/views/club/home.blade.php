@extends('layouts.club')

@section('title', 'Trang chủ')

@section('banner')
    <section class="page-banner">
        <img src="{{ $club->coverUrl() }}" alt="{{ $club->name }}">
    </section>
@endsection

@section('content')
    <section class="section">
        <div class="section-head">
            <h2>Trận đấu</h2>
            <a href="{{ route('stats.index') }}">Xem thống kê</a>
        </div>
        @if ($latestResult || $nextMatch)
            <div class="match-grid">
                @if ($latestResult)
                    @include('club.partials.match-card', ['match' => $latestResult, 'heading' => 'Kết quả gần nhất'])
                @endif
                @if ($nextMatch)
                    @include('club.partials.match-card', ['match' => $nextMatch, 'heading' => 'Trận tiếp theo'])
                @endif
            </div>
        @else
            <div class="empty">Chưa có trận đấu. Khi thêm lịch thi đấu, thông tin sẽ hiện ở đây.</div>
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
            <div class="section-head"><h2>Top 5 ghi bàn tháng</h2></div>
            @include('club.partials.player-list', ['players' => $topScorers, 'unit' => 'bàn', 'empty' => 'Chưa có bàn thắng trong tháng này.'])
        </div>
        <div>
            <div class="section-head"><h2>Top 5 MVP</h2></div>
            @include('club.partials.player-list', ['players' => $topMvps, 'unit' => 'điểm', 'empty' => 'Chưa có điểm trận đấu.'])
        </div>
    </section>
@endsection

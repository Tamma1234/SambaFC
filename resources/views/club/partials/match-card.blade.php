@php
    $kickoff = \Illuminate\Support\Carbon::parse($match['kickoff_at'])->timezone(config('app.timezone'));
@endphp

<article class="card match-card">
    <div class="badge">{{ $heading }} · {{ $match['status_label'] }}</div>
    <h3 class="match-title">{{ $match['title'] }}</h3>
    @if (!is_null($match['score_for']) && !is_null($match['score_against']))
        <div class="match-score">{{ $match['score_for'] }} - {{ $match['score_against'] }}</div>
    @endif
    <div class="match-meta">
        {{ $match['weekday_label'] }}, {{ $kickoff->format('H:i d/m/Y') }}
        @if ($match['venue'])
            · {{ $match['venue'] }}
        @endif
        · {{ $match['is_home'] ? 'Sân nhà' : 'Sân khách' }}
    </div>
    @if ($match['status'] === 'scheduled')
        <p class="match-extra match-attendance">
            Điểm danh:
            <span class="attending">{{ $match['attending_count'] }} tham gia</span>
            ·
            <span class="absent">{{ $match['absent_count'] }} không tham gia</span>
            @if ($match['pending_count'] > 0)
                · {{ $match['pending_count'] }} chưa xác nhận
            @endif
        </p>
    @endif
    @if ($match['scorers_label'])
        <p class="match-extra">Bàn thắng: {{ $match['scorers_label'] }}</p>
    @endif
    @if ($match['mvp_name'])
        <p class="match-extra match-mvp">MVP: {{ $match['mvp_name'] }}</p>
    @endif
</article>

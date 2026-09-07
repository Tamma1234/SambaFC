@if (count($players))
    <div class="card">
        @foreach ($players as $index => $player)
            <div class="player-row">
                <span class="rank">{{ $index + 1 }}</span>
                <img src="{{ $player['photo'] ?: $club->logoUrl() }}" alt="{{ $player['name'] }}">
                <strong>
                    @if (!is_null($player['jersey_number']))
                        #{{ $player['jersey_number'] }}
                    @endif
                    {{ $player['name'] }}
                </strong>
                <span class="value">{{ $player['value'] }} {{ $unit }}</span>
            </div>
        @endforeach
    </div>
@else
    <div class="empty">{{ $empty }}</div>
@endif

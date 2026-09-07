@extends('layouts.club')

@section('title', 'Thành viên')

@section('content')
    <h1 class="page-title">Thành viên</h1>
    <p class="page-lead">Danh sách cầu thủ Samba FC.</p>

    @if (count($members))
        <div class="squad-grid">
            @foreach ($members as $member)
                <article class="squad-card">
                    <img
                        src="{{ $member['photo'] ?: $club->logoUrl() }}"
                        alt="{{ $member['nickname'] }}"
                    >
                    <h2>{{ $member['nickname'] }}</h2>
                    <p>{{ $member['position'] }}</p>
                </article>
            @endforeach
        </div>
    @else
        <div class="empty" style="margin-top:18px;">Chưa có thành viên được công bố.</div>
    @endif
@endsection

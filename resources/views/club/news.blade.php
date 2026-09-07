@extends('layouts.club')

@section('title', 'Tin tức')

@section('content')
    <h1 class="page-title">Tin tức</h1>
    <p class="page-lead">Các bài viết, thông báo và lịch trận đấu tiếp theo của {{ $club->name }} sẽ được cập nhật tại đây.</p>
    <div class="empty" style="margin-top:18px;">Chưa có bài viết. Phần thêm / sửa / xóa tin tức sẽ làm ở bước sau.</div>
@endsection

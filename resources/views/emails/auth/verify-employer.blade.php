<!-- Kế thừa cái khung xương ở trên -->
@extends('emails.layouts.master')

@section('title', 'Xác thực tài khoản Nhà tuyển dụng')

@section('content')
<h2 style="color: #696cff; margin-top: 0;">Xin chào {{ $employerName }},</h2>

<p>Chào mừng bạn gia nhập <strong>JobNest</strong>. Để bắt đầu đăng tin tuyển dụng và tìm kiếm nhân tài, vui lòng xác
    thực địa chỉ email của bạn bằng cách bấm vào nút dưới đây:</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $verifyUrl }}" class="btn">Xác thực Email ngay</a>
</div>

<p>Liên kết này có thời hạn trong 60 phút. Nếu bạn không tạo tài khoản này, vui lòng bỏ qua email.</p>

<p>Trân trọng,<br><strong>Đội ngũ JobNest</strong></p>
@endsection
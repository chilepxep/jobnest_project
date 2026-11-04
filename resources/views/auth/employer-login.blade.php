@extends('layouts.blankLayout')

@section('title', 'Đăng nhập Nhà tuyển dụng')

@section('page-style')
<style>
/* Ảnh bên phải */
.auth-image-side {
    background-image: url("{{ asset('images/background_auth.png') }}");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-radius: 60px 0 0 60px;
    min-height: 100vh;
}

/* Form bên trái */
.auth-form-side {
    min-height: 100vh;
    background: #fff;
}

.auth-form-inner {
    width: 100%;
    max-width: 440px;
    /* Thu gọn form lại vì Login ít input */
}
</style>
@endsection

@section('content')
<div class="row g-0 w-100 m-0 bg-white">

    <!-- Bên trái: Form -->
    <div class="col-12 col-lg-6 auth-form-side d-flex align-items-center justify-content-center p-4 p-md-5">
        <div class="auth-form-inner">

            <img src="{{ asset('images/logo_jobnest.png') }}" alt="JobNest Logo" class="d-block mx-auto mb-2"
                style="max-height: 80px;">

            <h4 class="mb-1 text-center text-primary fw-bold">Chào mừng trở lại! 👋</h4>
            <p class="mb-4 text-center text-muted">Đăng nhập để quản lý tin tuyển dụng và tìm kiếm nhân tài.</p>

            <!-- Hiển thị lỗi Validate (nhập thiếu, sai định dạng) -->
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Hiển thị lỗi logic (sai tài khoản, bị khóa, từ chối duyệt) -->
            @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email đăng nhập</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}"
                        placeholder="hr@congty.com" required autofocus>
                </div>

                <div class="mb-3 form-password-toggle">
                    <div class="d-flex justify-content-between">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <a href="#">
                            <small>Quên mật khẩu?</small>
                        </a>
                    </div>
                    <input type="password" class="form-control" id="password" name="password" placeholder="············"
                        required>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember-me" name="remember">
                        <label class="form-check-label" for="remember-me">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                </div>

                <button class="btn btn-primary w-100 py-2" type="submit">Đăng nhập</button>
            </form>

            <p class="text-center mt-4 mb-0">
                <span>Doanh nghiệp mới?</span>
                <a href="{{ route('employer.register.step1') }}" class="fw-bold">Tạo tài khoản ngay</a>
            </p>
        </div>
    </div>

    <!-- Bên phải: Ảnh -->
    <div class="col-lg-6 d-none d-lg-block p-0">
        <div class="auth-image-side w-100 h-100"></div>
    </div>

</div>
@endsection
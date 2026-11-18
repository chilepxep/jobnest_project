@extends('layouts.blankLayout')

@section('title', 'Đăng ký Tài khoản Nhà tuyển dụng')

@section('page-style')
<style>
/* Ảnh bên phải */
.auth-image-side {
    background-image: url("{{ asset('images/background_auth.png') }}");
    background-size: cover;
    background-color: #fff;
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
}

/* Stepper */
.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.stepper-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.stepper-item::before {
    position: absolute;
    content: "";
    border-bottom: 2px solid #ccc;
    width: 100%;
    top: 15px;
    left: -50%;
    z-index: 2;
}

.stepper-item:first-child::before {
    content: none;
}

.step-counter {
    position: relative;
    z-index: 5;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ccc;
    color: #fff;
    font-weight: bold;
}

.stepper-item.active .step-counter,
.stepper-item.completed .step-counter {
    background: #f5a000;
}

.stepper-item.completed::before {
    border-color: #f5a000;
}

.step-name {
    margin-top: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #a1acb8;
    text-align: center;
}

.stepper-item.active .step-name {
    color: #f5a000;
}
</style>
@endsection

@section('content')

<div class="row g-0 w-100 m-0">

    <!-- Bên trái: Form -->
    <div class="col-12 col-lg-6 auth-form-side d-flex align-items-center justify-content-center p-4 p-md-5">
        <div class="auth-form-inner">

            <!-- Stepper -->
            <div class="stepper-wrapper">
                <div class="stepper-item active">
                    <div class="step-counter">1</div>
                    <div class="step-name">Tài khoản</div>
                </div>
                <div class="stepper-item">
                    <div class="step-counter">2</div>
                    <div class="step-name">Thông tin Công ty</div>
                </div>
                <div class="stepper-item">
                    <div class="step-counter">3</div>
                    <div class="step-name">Chờ xác duyệt</div>
                </div>
            </div>

            <img src="{{ asset('images/logo_jobnest.png') }}" alt="JobNest Logo" class="d-block mx-auto mb-2"
                style="max-height: 80px;">

            <h4 class="mb-1 text-center">Đăng ký Nhà tuyển dụng</h4>
            <p class="mb-4 text-center text-muted">Đưa thông tin tuyển dụng của bạn đến với hàng triệu ứng viên!</p>

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('employer.register.step1') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email đăng nhập (*)</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="{{ old('email', $step1Data['email'] ?? '') }}" placeholder="hr@congty.com" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu (*)</label>
                    <input type="password" class="form-control" id="password" name="password"
                        value="{{ old('password', $step1Data['password'] ?? '') }}" placeholder="············" required>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Xác nhận mật khẩu (*)</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                        placeholder="············" required>
                </div>

                <div class="mb-4">
                    <label for="phone" class="form-label">Số điện thoại cá nhân</label>
                    <input type="text" class="form-control" id="phone" name="phone"
                        value="{{ old('phone', $step1Data['phone'] ?? '') }}" placeholder="0901 234 567">
                </div>

                <button class="btn btn-success w-100" type="submit">Bước tiếp theo</button>
            </form>

            <p class="text-center mt-4 mb-0">
                <span>Đã có tài khoản công ty?</span>
                <a href="{{ route('login') }}">Đăng nhập ngay</a>
            </p>
        </div>
    </div>

    <!-- Bên phải: Ảnh -->
    <div class="col-lg-6 d-none d-lg-block bg-white">
        <div class="auth-image-side w-100"></div>
    </div>

</div>
@endsection
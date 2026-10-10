@extends('layouts.blankLayout')

@section('title', 'Bước 3: Chờ xác duyệt')

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
    /* MỞ RỘNG TỪ 440px LÊN 750px ĐỂ CHỨA 2 CỘT */
    max-width: 750px;
}

/* Stepper */
.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
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

<div class="row g-0 w-100 m-0 bg-white">
    <!-- Bên trái: Nội dung thông báo -->
    <div class="col-12 col-lg-7 auth-form-side d-flex align-items-center justify-content-center p-4 p-md-5">
        <div class="auth-form-inner text-center">

            <!-- Stepper (Bước 1 và 2 đã completed, Bước 3 active) -->
            <div class="stepper-wrapper">
                <div class="stepper-item completed">
                    <div class="step-counter"><i class="bx bx-check"></i></div>
                    <div class="step-name">Tài khoản</div>
                </div>
                <div class="stepper-item completed">
                    <div class="step-counter"><i class="bx bx-check"></i></div>
                    <div class="step-name">Thông tin Công ty</div>
                </div>
                <div class="stepper-item active">
                    <div class="step-counter">3</div>
                    <div class="step-name">Chờ xác duyệt</div>
                </div>
            </div>

            <!-- Icon Thông báo thành công -->
            <div class="mb-4 mt-2">
                <i class="bx bx-time-five text-success pulse-icon" style="font-size: 80px;"></i>
            </div>

            <h3 class="mb-3 text-warning fw-bold">Hồ sơ đang được xét duyệt!</h3>

            <p class="mb-2 fs-5 text-dark">
                Cảm ơn bạn đã đăng ký trở thành đối tác của <strong>Jobnest</strong>.
            </p>
            <p class="mb-4 text-muted" style="line-height: 1.6;">
                Thông tin doanh nghiệp của bạn đã được ghi nhận thành công và đang trong quá trình xét duyệt để đảm bảo
                chất
                lượng nền tảng. Quá trình này thường mất tối đa <strong>24 giờ làm việc</strong>. Chúng tôi sẽ gửi thông
                báo
                kết quả trực tiếp qua email của bạn.
            </p>

            <!-- Nút Đăng xuất vì chưa được vào Dashboard -->
            <div class="d-flex justify-content-center gap-3 mt-5">
                <!-- Nút Về trang chủ (Đúng như bạn yêu cầu) -->
                <a href="{{ route('homepage') }}" class="btn btn-outline-primary px-4 py-2">
                    <i class="bx bx-home-alt me-2"></i> Về trang chủ
                </a>

                <!-- Nút Đăng xuất (Rất cần thiết để họ đổi tài khoản khác nếu muốn) -->
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        Đăng xuất <i class="bx bx-log-out ms-2"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Bên phải: Ảnh -->
    <div class="col-lg-5 d-none d-lg-block p-0">
        <div class="auth-image-side w-100 h-100"></div>
    </div>
</div>

@endsection
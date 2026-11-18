@extends('layouts.blankLayout')

@section('title', 'Xác thực địa chỉ Email')

@section('page-style')
<style>
.authentication-wrapper {
    display: flex;
    flex-basis: 100%;
    min-height: 100vh;
    width: 100%;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f9;
}

.authentication-inner {
    width: 100%;
    max-width: 450px;
    padding: 24px;
}

.app-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
}

.verify-icon {
    width: 80px;
    height: 80px;
    background: #e7e7ff;
    color: #696cff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    margin: 0 auto 1.5rem auto;
}
</style>
@endsection

@section('content')
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
            <!-- Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-sm-5">

                    <!-- Logo -->
                    <div class="app-brand justify-content-center mb-4">
                        <a href="{{ route('homepage') }}" class="app-brand-link gap-2 text-decoration-none">
                            <span class="app-brand-text text-body fw-bolder fs-3">JobNest</span>
                        </a>
                    </div>

                    <!-- Icon Minh họa -->
                    <div class="d-flex justify-content-center mb-4">
                        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle"
                            style="width: 80px; height: 80px;">
                            <i class="bx bx-envelope-open" style="font-size: 40px;"></i>
                        </div>
                    </div>

                    <h4 class="mb-3 text-center fw-bold">Xác thực Email của bạn ✉️</h4>
                    <p class="mb-4 text-center text-muted lh-base">
                        Chúng tôi vừa gửi một đường dẫn xác thực đến địa chỉ <br>
                        <strong class="text-dark fs-6">{{ auth()->user()->email }}</strong> <br><br>
                        Vui lòng kiểm tra Hộp thư đến (và Hộp thư rác) để kích hoạt tài khoản Nhà tuyển dụng trước khi
                        tiếp tục.
                    </p>

                    <!-- Khối thông báo thành công (Gửi lại link HOẶC Đổi email thành công) -->
                    @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bx bx-check-circle me-2 fs-5"></i>
                        <div>Một đường dẫn xác thực mới vừa được gửi!</div>
                    </div>
                    @endif

                    @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bx bx-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    @endif

                    <!-- Nút Gửi lại Mail -->
                    <form class="mb-3" action="{{ route('verification.send') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary d-grid w-100 fw-bold py-2">
                            Gửi lại email xác thực
                        </button>
                    </form>

                    <!-- Form Đổi Email (Sử dụng Bootstrap Collapse cho hiệu ứng mượt mà) -->
                    <div class="mt-4 pt-3 border-top text-center">
                        <p class="text-muted mb-2">Bạn nhập sai địa chỉ email?</p>
                        <a class="text-primary fw-medium text-decoration-none" data-bs-toggle="collapse"
                            href="#collapseChangeEmail" role="button" aria-expanded="false"
                            aria-controls="collapseChangeEmail">
                            Đổi địa chỉ email <i class="bx bx-chevron-down align-middle"></i>
                        </a>

                        <!-- Thêm class "show" nếu có lỗi validate để form không bị đóng lại -->
                        <div class="collapse @error('new_email') show @enderror mt-3" id="collapseChangeEmail">
                            <form action="{{ route('verification.change-email') }}" method="POST">
                                @csrf
                                <div class="input-group">
                                    <input type="email" name="new_email"
                                        class="form-control @error('new_email') is-invalid @enderror"
                                        placeholder="Nhập email mới chính xác..." required>
                                    <button class="btn btn-outline-primary" type="submit">Cập nhật</button>

                                    <!-- Hiển thị lỗi validate chuẩn Bootstrap -->
                                    @error('new_email')
                                    <div class="invalid-feedback text-start mt-1">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Nút Đăng xuất -->
                    <div class="text-center mt-4">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit"
                                class="btn btn-link text-muted text-decoration-none p-0 custom-hover-text">
                                <i class="bx bx-log-out-circle me-1 align-middle"></i> Đăng nhập bằng tài khoản khác
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            <!-- /Card -->
        </div>
    </div>
</div>


@endsection
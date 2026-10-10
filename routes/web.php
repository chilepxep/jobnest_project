<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\EmployerAuthController;
use App\Http\Controllers\Employer\DashboardController;


Route::get('/', function () {
    return view('welcome');
})->name('homepage');;

// Route lấy danh sách Quận/Huyện 
Route::get('/api/districts/{province_id}', [App\Http\Controllers\Auth\EmployerAuthController::class, 'getDistricts'])->name('api.districts');

// ==========================================
// 2. NHÓM GUEST (CHỈ DÀNH CHO NGƯỜI CHƯA ĐĂNG NHẬP)
// ==========================================
Route::middleware('guest')->group(function () {
    // Đăng nhập
    Route::get('/employer/login', [EmployerAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/employer/login', [EmployerAuthController::class, 'login']);
    
    // Đăng ký Bước 1
    Route::get('/employer/register', [EmployerAuthController::class, 'showRegisterStep1'])->name('employer.register.step1'); // Đã thêm tiền tố employer.
    Route::post('/employer/register', [EmployerAuthController::class, 'processStep1']);
});

// ==========================================
// 3. NHÓM AUTH (BẮT BUỘC PHẢI ĐĂNG NHẬP)
// ==========================================
Route::middleware(['auth'])->group(function () {
    
    // Đăng xuất
    Route::post('/logout', [EmployerAuthController::class, 'logout'])->name('logout');

    // Khu vực dành riêng cho Nhà tuyển dụng
    Route::prefix('employer')->name('employer.')->group(function () {
        
        // Đăng ký thông tin Công ty (Bước 2)
        Route::get('/register-company', [EmployerAuthController::class, 'showRegisterStep2'])->name('register.step2');
        Route::post('/register-company', [EmployerAuthController::class, 'processStep2']);
        
        // Trang thông báo chờ duyệt (Bước 3)
        Route::get('/pending-approval', [EmployerAuthController::class, 'showStep3Pending'])->name('pending');

        // Dashboard (Chỉ vào được khi Admin đã duyệt)
        Route::middleware('employer.approved')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Employer\DashboardController::class, 'index'])->name('dashboard');
        });
        
    }); // Kết thúc nhóm prefix employer
    
}); // Kết thúc nhóm auth
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Auth\EmployerAuthController;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Employer\ApplicationController;


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
    Route::get('/employer/register', [EmployerAuthController::class, 'showRegisterStep1'])->name('employer.register.step1');
    Route::post('/employer/register', [EmployerAuthController::class, 'processStep1']);

    // Đăng ký thông tin Công ty (Bước 2)
    Route::get('/register-company', [EmployerAuthController::class, 'showRegisterStep2'])->name('employer.register.step2');
    Route::post('/register-company', [EmployerAuthController::class, 'processStep2']);
});


// ==========================================
// ROUTE XÁC THỰC KIỂM TRA PUBLIC
// ==========================================
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Đường dẫn xác thực không hợp lệ hoặc đã bị thay đổi.');
    }

    // Kiểm tra xem đã bấm trước đó chưa
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
        $message = '🎉 Tuyệt vời! Bạn đã xác thực email thành công.';
    } else {
        // Nếu đã bấm rồi thì báo thông báo khác
        $message = 'Tài khoản của bạn đã được xác thực từ trước.';
    }

    Auth::login($user);
    return redirect()->route('employer.pending')->with('success', $message);
})->middleware(['signed'])->name('verification.verify');

// ==========================================
// 3. NHÓM AUTH (BẮT BUỘC PHẢI ĐĂNG NHẬP)
// ==========================================
Route::middleware(['auth'])->group(function () {
    
    // Đăng xuất
    Route::post('/logout', [EmployerAuthController::class, 'logout'])->name('logout');

    //Xác thực mail
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

   Route::post('/email/change', [\App\Http\Controllers\Auth\EmployerAuthController::class, 'changeEmail'])->name('verification.change-email');
    

    // 3. Xử lý nút "Gửi lại email xác thực"
    Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->route('employer.pending')
                         ->with('success', 'Tài khoản của bạn đã được xác thực trước đó!');
    }
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');

    })->middleware(['throttle:3,1'])->name('verification.send');
    

    // Khu vực dành riêng cho Nhà tuyển dụng
    Route::prefix('employer')->name('employer.')->group(function () {
           
        // 3. Nhóm yêu cầu Admin Duyệt (Đã login, ĐÃ verify mail)
            Route::middleware(['auth', 'verified'])->group(function () {
                Route::get('/pending-approval', [EmployerAuthController::class, 'showStep3Pending'])->name('pending');
            });
        
        // Dashboard
        Route::middleware(['employer.approved', 'verified'])->group(function () {

            Route::get('/dashboard', [App\Http\Controllers\Employer\DashboardController::class, 'index'])->name('dashboard');
            
            // Quản lý Tin tuyển dụng (3 trang theo menu Sidebar)
            Route::get('/jobs/active', [App\Http\Controllers\Employer\JobController::class, 'activeJobs'])->name('jobs.active');
            Route::get('/jobs/paused', [App\Http\Controllers\Employer\JobController::class, 'pausedJobs'])->name('jobs.paused');
            Route::get('/jobs/expired', [App\Http\Controllers\Employer\JobController::class, 'expiredJobs'])->name('jobs.expired');

            // Tạo mới, Xem, Sửa, Xóa & Đổi trạng thái nhanh
            Route::get('/jobs/create', [App\Http\Controllers\Employer\JobController::class, 'create'])->name('jobs.create');
            Route::post('/jobs', [App\Http\Controllers\Employer\JobController::class, 'store'])->name('jobs.store');
            Route::get('/jobs/{job}', [App\Http\Controllers\Employer\JobController::class, 'show'])->name('jobs.show');
            Route::get('/jobs/{job}/edit', [App\Http\Controllers\Employer\JobController::class, 'edit'])->name('jobs.edit');
            Route::put('/jobs/{job}', [App\Http\Controllers\Employer\JobController::class, 'update'])->name('jobs.update');
            Route::patch('/jobs/{job}/status', [App\Http\Controllers\Employer\JobController::class, 'changeStatus'])->name('jobs.status');
            Route::delete('/jobs/{job}', [App\Http\Controllers\Employer\JobController::class, 'destroy'])->name('jobs.destroy');

            //Quản lý ứng viên
            Route::prefix('/applications')->name('applications.')->group(function () {
            Route::get('/', [ApplicationController::class, 'index'])->name('index');
            Route::get('/{application}', [ApplicationController::class, 'show'])->name('show');
            Route::patch('/{application}/status', [ApplicationController::class, 'updateStatus'])->name('update-status');
            Route::get('/{application}/cv', [ApplicationController::class, 'viewCv'])->name('cv');
                });
            });

        
    }); // Kết thúc nhóm prefix employer
    
}); // Kết thúc nhóm auth
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckEmployerApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       $user = Auth::user();
      // 1. Chặn ngay nếu không phải là Employer
        if ($user->role !== 'employer') {
            abort(403, 'Bạn không có quyền truy cập khu vực dành cho Doanh nghiệp.');
        }

        $employer = $user->employer;

        // 2. Nếu chưa đăng ký thông tin công ty -> Trả về Bước 2
        if (!$employer || !$employer->company) {
            return redirect()->route('employer.register.step2');
        }
        
        // 3. Nếu công ty đang chờ duyệt -> Trả về Bước 3 (Trang thông báo)
        if ($employer->company->trang_thai === 'cho_duyet') {
            return redirect()->route('employer.pending');
        }

        // 4. Nếu công ty bị từ chối -> Đăng xuất và đuổi ra ngoài
        if ($employer->company->trang_thai === 'tu_choi') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Hồ sơ công ty của bạn đã bị từ chối. Vui lòng liên hệ Admin.']);
        }

        // 5. Nếu trạng thái là 'da_duyet' -> Cho phép đi tiếp vào Dashboard
        return $next($request);
    }
}
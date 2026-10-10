<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{

    private const CACHE_TTL_MINUTES = 5;

    public function index()
    {
        // 1. Tài khoản HR + công ty 
        $employer = Employer::with('company:id,ten_cong_ty')
            ->select('id', 'user_id', 'company_id', 'ho_ten', 'chuc_vu')
            ->where('user_id', Auth::id())
            ->first();
 
        if (!$employer) {
            abort(403, 'Tài khoản của bạn chưa được liên kết với công ty nào.');
        }
 
        // 2. Số liệu thống kê tính theo CÔNG TY nên cache chung
        //    cho mọi HR cùng công ty. Chỉ cache mảng thuần, không cache Eloquent model.
        $stats = Cache::remember(
            self::cacheKey($employer->company_id),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->buildStats($employer->company_id)
        );
 
        return view('employer.dashboard', ['employer' => $employer] + $stats);
    }


     public static function cacheKey(int $companyId): string
    {
        return "employer_dashboard:{$companyId}";
    }

    private function buildStats(int $companyId): array
    {
        // 6 tháng gần nhất, cũ -> mới.
        // Gọi startOfMonth() TRƯỚC subMonths() để tránh lỗi tràn ngày
        // (vd 31/10 trừ 1 tháng sẽ ra 01/10 thay vì tháng 9).
        $months = collect(range(5, 0))
            ->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $from = $months->first();
 
        // Q1: Tin tuyển dụng gom theo trạng thái -> 1 query thay cho 4 query count() riêng lẻ
        $jobsByStatus = Job::where('company_id', $companyId)
            ->selectRaw('trang_thai, COUNT(*) AS total')
            ->groupBy('trang_thai')
            ->pluck('total', 'trang_thai');
 
        // Q2: Số tin tuyển dụng tạo mới theo tháng (đổi sang 'ngay_dang' nếu muốn tính theo ngày duyệt)
        $jobsByMonth = Job::where('company_id', $companyId)
            ->where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');
 
        // Q3: Hồ sơ ứng tuyển gom theo trạng thái -> 1 query thay cho 4 query whereHas
        $appsByStatus = $this->applicationsOfCompany($companyId)
            ->selectRaw('applications.trang_thai, COUNT(*) AS total')
            ->groupBy('applications.trang_thai')
            ->pluck('total', 'trang_thai');
 
        // Q4: Số hồ sơ ứng tuyển theo tháng
        $appsByMonth = $this->applicationsOfCompany($companyId)
            ->where('applications.ngay_ung_tuyen', '>=', $from)
            ->selectRaw("DATE_FORMAT(applications.ngay_ung_tuyen, '%Y-%m') AS ym, COUNT(*) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');
 
        return [
            // Biểu đồ cột/đường theo 6 tháng
            'chartCategories'   => $months->map(fn ($m) => 'Tháng ' . $m->format('n'))->all(),
            'chartData'         => $months->map(fn ($m) => (int) ($appsByMonth[$m->format('Y-m')] ?? 0))->all(),
            'chartDataTinTuyen' => $months->map(fn ($m) => (int) ($jobsByMonth[$m->format('Y-m')] ?? 0))->all(),
 
            // Biểu đồ tròn: ứng viên [Đã được chọn, Từ chối]
            'chartDataCircle'       => [
                (int) ($appsByStatus['da_tuyen'] ?? 0),
                (int) ($appsByStatus['tu_choi'] ?? 0),
            ],
            'charDataTotelUngVien'  => (int) $appsByStatus->sum(),
            'chartDataCircleLabel'  => ['Đã được chọn', 'Từ chối'],
 
            // Biểu đồ tròn: tin tuyển dụng [Hoạt động, Tạm dừng, Hết hạn]
            'chartDataCircleTin'      => [
                (int) ($jobsByStatus['dang_tuyen'] ?? 0),
                (int) ($jobsByStatus['da_dong'] ?? 0),   // "Tạm dừng" = NTD tự đóng tin (da_dong)
                (int) ($jobsByStatus['het_han'] ?? 0),
            ],
            'charDataTotelTin'        => (int) $jobsByStatus->sum(),
            'chartDataCircleLabelTin' => ['Hoạt động', 'Tạm dừng', 'Hết Hạn'],
        ];
    }

    private function applicationsOfCompany(int $companyId): Builder
    {
        return Application::query()
            ->join('jobs', 'jobs.id', '=', 'applications.job_id')
            ->where('jobs.company_id', $companyId);
    }
}
<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\Industry;
use App\Models\Skill;
use App\Models\Province;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function create()
    {
        $industries = Industry::all();
        $skills = Skill::all();
        $provinces = Province::all();
        
        return view('employer.jobs.create', compact('industries', 'skills', 'provinces'));
    }


    public function store(Request $request)
    {
        // 1. Validate dữ liệu
        $request->validate([
            'tieu_de' => 'required|string|max:255',
            'mo_ta_cong_viec' => 'required|string',
            'loai_hinh_lam_viec' => 'required|in:toan_thoi_gian,ban_thoi_gian,thuc_tap,tu_do,tu_xa',
            'cap_bac' => 'required|in:thuc_tap_sinh,nhan_vien,truong_nhom,quan_ly,truong_phong,giam_doc',
            'han_nop_ho_so' => 'required|date|after:today',
            'industry_ids' => 'required|array', 
            'skill_ids' => 'required|array',    
            'locations'                => 'required|array|min:1',
            'locations.*.province_id'  => 'required|exists:provinces,id',
            'locations.*.district_id'  => 'nullable|exists:districts,id',
            'locations.*.dia_chi_chi_tiet' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $employer = Auth::user()->employer;

            // 2. Tạo Slug (từ tiêu đề + random chuỗi để chống trùng)
            $slug = Str::slug($request->tieu_de) . '-' . time();

            // 3. Lưu bảng jobs
            $job = Job::create([
                'company_id' => $employer->company_id,
                'employer_id' => $employer->id,
                'tieu_de' => $request->tieu_de,
                'slug' => $slug,
                'mo_ta_cong_viec' => $request->mo_ta_cong_viec,
                'yeu_cau_ung_vien' => $request->yeu_cau_ung_vien,
                'quyen_loi' => $request->quyen_loi,
                'loai_hinh_lam_viec' => $request->loai_hinh_lam_viec,
                'cap_bac' => $request->cap_bac,
                'kinh_nghiem_toi_thieu' => $request->kinh_nghiem_toi_thieu,
                'luong_thoa_thuan' => $request->has('luong_thoa_thuan') ? 1 : 0,
                'luong_toi_thieu' => $request->luong_toi_thieu,
                'luong_toi_da' => $request->luong_toi_da,
                'so_luong_tuyen' => $request->so_luong_tuyen ?? 1,
                'han_nop_ho_so' => $request->han_nop_ho_so,
                'trang_thai' => 'nhap', 
            ]);

                // 2. Lưu nhiều địa điểm vào bảng job_locations
            $primaryKey = $request->input('primary_location');
            foreach ($request->locations as $index => $loc) {
                JobLocation::create([
                    'job_id'            => $job->id,
                    'province_id'       => $loc['province_id'],
                    'district_id'       => !empty($loc['district_id']) ? $loc['district_id'] : null,
                    'dia_chi_chi_tiet'  => $loc['dia_chi_chi_tiet'] ?? null,
                    'la_dia_diem_chinh' => ((string)$index === (string)$primaryKey) ? 1 : 0,
                ]);
            }

            // 5. Lưu bảng trung gian job_industries (Dùng hàm attach)
            $job->industries()->attach($request->industry_ids);

            // 6. Lưu bảng trung gian job_skills (Dùng hàm attach)
            $job->skills()->attach($request->skill_ids);

            DB::commit();

            return redirect()->route('employer.jobs.active', ['subtab' => 'pending'])->with('success', 'Đăng tin thành công! Tin đang ở trạng thái Nháp chờ bạn duyệt.');

        } catch (\Exception $e) {
            DB::rollBack();
            // In lỗi ra để debug nếu cần: dd($e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        }
    }

    // 1. Trang: Việc làm Đang hoạt động
    public function activeJobs(Request $request)
    {
        return $this->renderJobList($request, 'active');
    }

    // 2. Trang: Việc làm Đã tạm dừng (Gồm cả Nháp & Bị từ chối)
    public function pausedJobs(Request $request)
    {
        $employer = Auth::user()->employer;

        $query = Job::with(['locations.province', 'industries'])
            ->where('company_id', $employer->company_id)
            ->whereIn('trang_thai', ['da_dong', 'tu_choi']);

        if ($request->filled('q')) {
            $kw = trim($request->q);
            $query->where('tieu_de', 'like', "%{$kw}%");
        }
        if ($request->filled('trang_thai') && in_array($request->trang_thai, ['da_dong', 'tu_choi'])) {
            $query->where('trang_thai', $request->trang_thai);
        }
        if ($request->filled('loai_hinh')) {
            $query->where('loai_hinh_lam_viec', $request->loai_hinh);
        }

        $jobs = $query->latest('updated_at')->paginate(10)->withQueryString();
        $totalCount = Job::where('company_id', $employer->company_id)
            ->whereIn('trang_thai', ['da_dong', 'tu_choi'])
            ->count();

        return view('employer.jobs.paused', compact('jobs', 'totalCount'));
    }

    // 3. Trang: Việc làm Đã hết hạn
    public function expiredJobs(Request $request)
    {
        $employer = Auth::user()->employer;

        // Tự động chuyển các tin đang tuyển nhưng đã qua hạn nộp sang 'het_han'
        Job::where('company_id', $employer->company_id)
            ->where('trang_thai', 'dang_tuyen')
            ->whereDate('han_nop_ho_so', '<', now()->toDateString())
            ->update(['trang_thai' => 'het_han']);

        $query = Job::with(['locations.province', 'industries'])
            ->where('company_id', $employer->company_id)
            ->where('trang_thai', 'het_han');

        if ($request->filled('q')) {
            $kw = trim($request->q);
            $query->where('tieu_de', 'like', "%{$kw}%");
        }
        if ($request->filled('loai_hinh')) {
            $query->where('loai_hinh_lam_viec', $request->loai_hinh);
        }

        $jobs = $query->latest('han_nop_ho_so')->paginate(10)->withQueryString();
        $totalCount = Job::where('company_id', $employer->company_id)
            ->where('trang_thai', 'het_han')
            ->count();

        return view('employer.jobs.expired', compact('jobs', 'totalCount'));
    }

    private function renderJobList(Request $request, string $tab)
    {
        $employer = Auth::user()->employer;

        // 1. Tự động chuyển các tin đang tuyển đã qua hạn nộp sang 'het_han'
        Job::where('company_id', $employer->company_id)
            ->where('trang_thai', 'dang_tuyen')
            ->whereDate('han_nop_ho_so', '<', now()->toDateString())
            ->update(['trang_thai' => 'het_han']);

        // 2. Xác định subtab cho trang 'active': 'active' | 'pending' | 'draft'
        $subtab = $request->query('subtab', 'active');
        if (!in_array($subtab, ['active', 'pending', 'draft'])) {
            $subtab = 'active';
        }

        // 3. Đếm số lượng từng mục để hiển thị lên Badge
        $baseQuery = Job::where('company_id', $employer->company_id);
        $counts = [
            'active_running' => (clone $baseQuery)->where('trang_thai', 'dang_tuyen')->count(),
            'active_pending' => (clone $baseQuery)->where('trang_thai', 'cho_duyet')->count(),
            'active_draft'   => (clone $baseQuery)->where('trang_thai', 'nhap')->count(),
            'paused'         => (clone $baseQuery)->whereIn('trang_thai', ['da_dong', 'tu_choi'])->count(),
            'expired'        => (clone $baseQuery)->where('trang_thai', 'het_han')->count(),
        ];

        // 4. Query chính lấy danh sách
        // Nếu Model Job của bạn đã có hàm applications(), bạn có thể thêm ->withCount('applications')
        $query = Job::with(['locations.province', 'industries'])
            ->where('company_id', $employer->company_id);

        if ($tab === 'active') {
            $statusMap = [
                'active'  => 'dang_tuyen',
                'pending' => 'cho_duyet',
                'draft'   => 'nhap',
            ];
            $query->where('trang_thai', $statusMap[$subtab]);
        } elseif ($tab === 'paused') {
            $query->whereIn('trang_thai', ['da_dong', 'tu_choi']);
        } else {
            $query->where('trang_thai', 'het_han');
        }

        // 5. Bộ lọc tìm kiếm
        if ($request->filled('q')) {
            $kw = trim($request->q);
            $query->where('tieu_de', 'like', "%{$kw}%");
        }
        if ($tab === 'paused' && $request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }
        if ($request->filled('loai_hinh')) {
            $query->where('loai_hinh_lam_viec', $request->loai_hinh);
        }

        $jobs = $query->latest('updated_at')->paginate(10)->withQueryString();

        return view('employer.jobs.index', compact('jobs', 'tab', 'subtab', 'counts'));
    }

        
    public function changeStatus(Request $request, Job $job)
    {
        $employer = Auth::user()->employer;
        if ($job->company_id !== $employer->company_id) {
            abort(403);
        }

        $newStatus = $request->input('trang_thai');
        if (!in_array($newStatus, ['dang_tuyen', 'da_dong', 'cho_duyet'])) {
            return back()->with('error', 'Trạng thái không hợp lệ.');
        }

        $job->update(['trang_thai' => $newStatus]);

        $msg = match ($newStatus) {
            'da_dong'    => 'Đã tạm dừng hiển thị tin tuyển dụng.',
            'dang_tuyen' => 'Đã kích hoạt lại tin tuyển dụng!',
            'cho_duyet'  => 'Đã gửi tin tuyển dụng chờ Admin phê duyệt!',
        };

        return back()->with('success', $msg);
    }

    // Xóa tin tuyển dụng
    public function destroy(Job $job)
    {
        $employer = Auth::user()->employer;
        if ($job->company_id !== $employer->company_id) {
            abort(403);
        }

        $job->delete();
        return back()->with('success', 'Đã xóa tin tuyển dụng thành công!');
    }
       
    public function edit(Job $job)
    {
        $employer = Auth::user()->employer;

        // Bảo mật: Chặn nếu cố tình sửa tin của công ty khác
        if ($job->company_id !== $employer->company_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa tin tuyển dụng này.');
        }

        // Load sẵn dữ liệu từ 3 bảng liên quan
        $job->load(['locations', 'industries', 'skills']);

        $industries = Industry::all();
        $skills = Skill::all();
        $provinces = Province::all();

        return view('employer.jobs.edit', compact('job', 'industries', 'skills', 'provinces'));
    }

    public function update(Request $request, Job $job)
    {
        $employer = Auth::user()->employer;

        if ($job->company_id !== $employer->company_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa tin tuyển dụng này.');
        }

        $request->validate([
            'tieu_de'                      => 'required|string|max:255',
            'mo_ta_cong_viec'              => 'required|string',
            'loai_hinh_lam_viec'           => 'required|in:toan_thoi_gian,ban_thoi_gian,thuc_tap,tu_do,tu_xa',
            'cap_bac'                      => 'required|in:thuc_tap_sinh,nhan_vien,truong_nhom,quan_ly,truong_phong,giam_doc',
            'han_nop_ho_so'                => 'required|date',
            'industry_ids'                 => 'required|array|min:1|max:3',
            'skill_ids'                    => 'required|array|min:1',
            'locations'                    => 'required|array|min:1',
            'locations.*.province_id'      => 'required|exists:provinces,id',
            'locations.*.district_id'      => 'nullable|exists:districts,id',
            'locations.*.dia_chi_chi_tiet' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Xác định trạng thái mới dựa trên nút bấm của người dùng
            $actionStatus = $request->input('action_status');
            $newStatus = $job->trang_thai;
            $lyDoTuChoi = $job->ly_do_tu_choi;

            if ($actionStatus === 'nhap') {
                $newStatus = 'nhap';
            } elseif ($actionStatus === 'da_dong') {
                $newStatus = 'da_dong';
            } elseif ($actionStatus === 'submit') {
                // Nếu tin đang tuyển hoặc tạm dừng (đã từng được duyệt) và hạn nộp còn hiệu lực -> cho phép 'dang_tuyen'
                // Nếu tin là nháp hoặc bị từ chối -> chuyển sang 'cho_duyet'
                if (in_array($job->trang_thai, ['dang_tuyen', 'da_dong', 'het_han']) && $request->han_nop_ho_so >= now()->toDateString()) {
                    $newStatus = 'dang_tuyen';
                } else {
                    $newStatus = 'cho_duyet';
                }
                $lyDoTuChoi = null; // Xóa lý do từ chối cũ sau khi HR đã sửa bài
            }

            // 1. Cập nhật bảng jobs
            $job->update([
                'tieu_de'               => $request->tieu_de,
                'mo_ta_cong_viec'       => $request->mo_ta_cong_viec,
                'yeu_cau_ung_vien'      => $request->yeu_cau_ung_vien,
                'quyen_loi'             => $request->quyen_loi,
                'loai_hinh_lam_viec'    => $request->loai_hinh_lam_viec,
                'cap_bac'               => $request->cap_bac,
                'kinh_nghiem_toi_thieu' => $request->kinh_nghiem_toi_thieu ?? 0,
                'luong_thoa_thuan'      => $request->boolean('luong_thoa_thuan'),
                'luong_toi_thieu'       => $request->boolean('luong_thoa_thuan') ? null : $request->luong_toi_thieu,
                'luong_toi_da'          => $request->boolean('luong_thoa_thuan') ? null : $request->luong_toi_da,
                'so_luong_tuyen'        => $request->so_luong_tuyen ?? 1,
                'han_nop_ho_so'         => $request->han_nop_ho_so,
                'trang_thai'            => $newStatus,
                'ly_do_tu_choi'         => $lyDoTuChoi,
            ]);

            // 2. Cập nhật bảng job_locations (Xóa cũ -> Thêm mới)
            $job->locations()->delete();
            $primaryKey = $request->input('primary_location', 0);

            foreach ($request->locations as $index => $loc) {
                JobLocation::create([
                    'job_id'            => $job->id,
                    'province_id'       => $loc['province_id'],
                    'district_id'       => !empty($loc['district_id']) ? $loc['district_id'] : null,
                    'dia_chi_chi_tiet'  => $loc['dia_chi_chi_tiet'] ?? null,
                    'la_dia_diem_chinh' => ((string)$index === (string)$primaryKey) ? 1 : 0,
                ]);
            }

            // 3. Đồng bộ bảng trung gian job_industries & job_skills bằng sync()
            $job->industries()->sync($request->industry_ids);
            $job->skills()->sync($request->skill_ids);

            DB::commit();

            // Điều hướng thông minh về đúng danh sách tương ứng với trạng thái vừa lưu
            return match ($newStatus) {
                'dang_tuyen' => redirect()->route('employer.jobs.active', ['subtab' => 'active'])
                                          ->with('success', 'Đã cập nhật tin tuyển dụng thành công!'),
                'cho_duyet'  => redirect()->route('employer.jobs.active', ['subtab' => 'pending'])
                                          ->with('success', 'Đã cập nhật và gửi tin chờ Admin phê duyệt!'),
                'nhap'       => redirect()->route('employer.jobs.active', ['subtab' => 'draft'])
                                          ->with('success', 'Đã lưu thay đổi vào bản nháp!'),
                'da_dong'    => redirect()->route('employer.jobs.paused')
                                          ->with('success', 'Đã cập nhật và tạm dừng tin tuyển dụng!'),
                default      => redirect()->route('employer.jobs.expired')
                                          ->with('success', 'Đã cập nhật tin tuyển dụng!'),
            };

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật: ' . $e->getMessage());
        }
    }

}
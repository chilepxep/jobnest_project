<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\District;
use App\Models\Employer;
use App\Models\Industry;
use App\Models\Job;
use App\Models\Province;
use App\Models\Skill;
use Carbon\Carbon;
use App\Models\JobLocation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    private const TRANSITIONS = [
        'nhap'       => ['cho_duyet'],
        'cho_duyet'  => ['nhap'],          // rút lại để sửa
        'tu_choi'    => ['cho_duyet'],     // gửi duyệt lại
        'dang_tuyen' => ['da_dong'],       // tạm dừng
        'da_dong'    => ['dang_tuyen'],    // bật lại: chỉ khi tin ĐÃ TỪNG được duyệt và còn hạn (kiểm tra bên dưới)
        'het_han'    => [],                // gia hạn bằng cách sửa tin + gửi lại (update)
    ];
        /** Sửa các trường này trên tin đã từng được duyệt thì phải duyệt lại (đặt false để bỏ quy tắc). */
    private const REVIEW_AGAIN_ON_CONTENT_CHANGE = true;
    private const CONTENT_FIELDS = ['tieu_de', 'mo_ta_cong_viec', 'yeu_cau_ung_vien', 'quyen_loi'];

    public function create()
    {
        Gate::authorize('create', Job::class);

        $industries = Industry::all();
        $skills = Skill::all();
        $provinces = Province::all();

        return view('employer.jobs.create', compact('industries', 'skills', 'provinces'));
    }


    public function store(Request $request)
    {
        Gate::authorize('create', Job::class); // NTD + công ty đã được duyệt (xem JobPolicy)
        $employer = $this->employer();

        // Form có thể gửi action_status = 'submit' để gửi duyệt ngay; mặc định lưu nháp
        $status = $request->input('action_status') === 'submit' ? 'cho_duyet' : 'nhap';
        $data = $this->validated($request, requireFutureDeadline: true);

        // TODO(gói dịch vụ): kiểm tra hạn mức so_tin_dang_toi_da của gói đang dùng tại đây

        try {
            DB::transaction(function () use ($request, $data, $employer, $status) {
                $job = Job::create($this->jobAttributes($data, $request) + [
                    'company_id'  => $employer->company_id,
                    'employer_id' => $employer->id,
                    'slug'        => $this->makeSlug($data['tieu_de']),
                    'trang_thai'  => $status,
                ]);

                $this->syncLocations($job, $data['locations'], $request->input('primary_location'));
                $job->industries()->attach($data['industry_ids']);
                $job->skills()->attach($data['skill_ids']);
            });
        } catch (\Throwable $e) {
            report($e); // ghi log để còn biết nguyên nhân, KHÔNG trả chi tiết lỗi cho người dùng
            return back()->withInput()->with('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        }

        // Chuyển đúng tab theo trạng thái vừa tạo (bản cũ tạo 'nhap' nhưng chuyển sang tab 'pending')
        if ($status === 'cho_duyet') {
            return redirect()->route('employer.jobs.active', ['subtab' => 'pending'])
                ->with('success', 'Đã gửi tin tuyển dụng chờ Admin phê duyệt!');
        }

        return redirect()->route('employer.jobs.active', ['subtab' => 'draft'])
            ->with('success', 'Đã lưu tin vào bản nháp. Bạn có thể chỉnh sửa và gửi duyệt khi sẵn sàng.');
    }

    // 1. Trang: Việc làm Đang hoạt động
    public function activeJobs(Request $request)
    {
        return $this->renderJobList($request);
    }

    // 2. Trang: Việc làm Đã tạm dừng (Gồm cả Nháp & Bị từ chối)
    public function pausedJobs(Request $request)
    {
        $employer = $this->employer();

        $jobs = $this->jobsQuery($request, $employer, ['da_dong', 'tu_choi'])
            ->latest('updated_at')->paginate(10)->withQueryString();
        $totalCount = (int) $this->statusCounts($employer->company_id)->only(['da_dong', 'tu_choi'])->sum();

        return view('employer.jobs.paused', compact('jobs', 'totalCount'));
    }

    // 3. Trang: Việc làm Đã hết hạn
    public function expiredJobs(Request $request)
    {
        $employer = $this->employer();

        // Không còn UPDATE khi mở trang (GET không nên ghi DB). Việc chuyển 'het_han' do lịch chạy hằng ngày đảm nhiệm.
        $jobs = $this->jobsQuery($request, $employer, ['het_han'])
            ->latest('han_nop_ho_so')->paginate(10)->withQueryString();
        $totalCount = (int) $this->statusCounts($employer->company_id)->get('het_han', 0);

        return view('employer.jobs.expired', compact('jobs', 'totalCount'));
    }

    private function renderJobList(Request $request)
    {
        $employer = $this->employer();
        $tab = 'active';

        $subtab = $request->query('subtab', 'active');
        if (!in_array($subtab, ['active', 'pending', 'draft'], true)) {
            $subtab = 'active';
        }

        // 1 truy vấn GROUP BY thay cho 5 truy vấn count() (dùng index idx_jobs_company_status)
        $by = $this->statusCounts($employer->company_id);
        $counts = [
            'active_running' => (int) ($by['dang_tuyen'] ?? 0),
            'active_pending' => (int) ($by['cho_duyet'] ?? 0),
            'active_draft'   => (int) ($by['nhap'] ?? 0),
            'paused'         => (int) (($by['da_dong'] ?? 0) + ($by['tu_choi'] ?? 0)),
            'expired'        => (int) ($by['het_han'] ?? 0),
        ];

        $statusMap = ['active' => 'dang_tuyen', 'pending' => 'cho_duyet', 'draft' => 'nhap'];
        $jobs = $this->jobsQuery($request, $employer, [$statusMap[$subtab]])
            ->latest('updated_at')->paginate(10)->withQueryString();

        return view('employer.jobs.index', compact('jobs', 'tab', 'subtab', 'counts'));
    }

    private function jobsQuery(Request $request, Employer $employer, array $statuses)
    {
        $query = Job::with(['locations.province', 'industries'])
            ->where('company_id', $employer->company_id)
            ->whereIn('trang_thai', $statuses);

        if ($request->filled('q')) {
            $kw = addcslashes(trim($request->q), '%_\\'); // escape ký tự đại diện của LIKE
            $query->where('tieu_de', 'like', "%{$kw}%");
        }
        if ($request->filled('trang_thai') && in_array($request->trang_thai, $statuses, true)) {
            $query->where('trang_thai', $request->trang_thai);
        }
        if ($request->filled('loai_hinh')) {
            $query->where('loai_hinh_lam_viec', $request->loai_hinh);
        }

        return $query;
    }

    private function statusCounts(int $companyId): Collection
    {
        return Job::where('company_id', $companyId)
            ->selectRaw('trang_thai, COUNT(*) AS total')
            ->groupBy('trang_thai')
            ->pluck('total', 'trang_thai');
    }

        
     public function changeStatus(Request $request, Job $job)
    {
        Gate::authorize('manage', $job);

        $new = $request->input('trang_thai');

        // Máy trạng thái: chỉ cho phép các chuyển hợp lệ từ trạng thái HIỆN TẠI của tin
        if (!in_array($new, self::TRANSITIONS[$job->trang_thai] ?? [], true)) {
            return back()->with('error', 'Không thể chuyển tin từ trạng thái hiện tại sang trạng thái này.');
        }

        if ($new === 'dang_tuyen' && $job->ngay_dang === null) {
            // ngay_dang do Admin gán khi duyệt; chưa có nghĩa là tin chưa từng được duyệt -> phải gửi duyệt
            return back()->with('error', 'Tin này chưa từng được duyệt, vui lòng gửi duyệt.');
        }

        if (in_array($new, ['dang_tuyen', 'cho_duyet'], true) && !$this->deadlineStillValid($job->han_nop_ho_so)) {
            return back()->with('error', 'Hạn nộp hồ sơ đã qua. Hãy chỉnh sửa tin và chọn hạn mới.');
        }

        // TODO(gói dịch vụ): kiểm tra hạn mức so_tin_dang_toi_da khi $new === 'dang_tuyen'

        $job->trang_thai = $new;
        if ($new === 'cho_duyet') {
            $job->ly_do_tu_choi = null;
        }
        $job->save();

        $msg = match ($new) {
            'da_dong'    => 'Đã tạm dừng hiển thị tin tuyển dụng.',
            'dang_tuyen' => 'Đã kích hoạt lại tin tuyển dụng!',
            'cho_duyet'  => 'Đã gửi tin tuyển dụng chờ Admin phê duyệt!',
            'nhap'       => 'Đã rút tin về bản nháp.',
        };

        return back()->with('success', $msg);
    }

    // Xóa tin tuyển dụng
    public function destroy(Job $job)
    {
        Gate::authorize('manage', $job);

        // Chỉ xoá cứng tin chưa từng công khai. Tin khác: tạm dừng (muốn "xoá" mọi tin thì dùng SoftDeletes).
        if (!in_array($job->trang_thai, ['nhap', 'tu_choi'], true)) {
            return back()->with('error', 'Chỉ có thể xóa tin nháp hoặc tin bị từ chối. Với tin khác, hãy chọn "Tạm dừng".');
        }

        // applications.job_id không có ON DELETE CASCADE -> xoá tin đã có hồ sơ sẽ lỗi khoá ngoại
        if (Application::where('job_id', $job->id)->exists()) {
            return back()->with('error', 'Tin này đã có hồ sơ ứng tuyển nên không thể xóa.');
        }

        try {
            $job->delete();
        } catch (QueryException $e) {
            report($e);
            return back()->with('error', 'Không thể xóa tin tuyển dụng này.');
        }

        return back()->with('success', 'Đã xóa tin tuyển dụng thành công!');
    }
       
    public function edit(Job $job)
    {
        Gate::authorize('manage', $job);

        $job->load(['locations', 'industries', 'skills']);

        $industries = Industry::all();
        $skills = Skill::all();
        $provinces = Province::all();

        return view('employer.jobs.edit', compact('job', 'industries', 'skills', 'provinces'));
    }

    public function update(Request $request, Job $job)
    {
        Gate::authorize('manage', $job);

        $action = $request->input('action_status'); // 'nhap' | 'da_dong' | 'submit' | null
        $data = $this->validated($request, requireFutureDeadline: $action === 'submit');

        $job->fill($this->jobAttributes($data, $request));
        // Lưu ý: nếu nội dung cũ chưa từng qua sanitize, lần sửa đầu có thể bị coi là "đã đổi nội dung"
        $contentChanged = $job->isDirty(self::CONTENT_FIELDS);

        // Có thể ném ValidationException (Laravel tự quay lại form kèm thông báo lỗi)
        $newStatus = $this->resolveStatusOnUpdate($job, $action, $data['han_nop_ho_so'], $contentChanged);
        $job->trang_thai = $newStatus;
        if ($newStatus === 'cho_duyet') {
            $job->ly_do_tu_choi = null; // HR đã sửa bài và gửi lại -> xoá lý do từ chối cũ
        }

        try {
            DB::transaction(function () use ($job, $data, $request) {
                $job->save();
                $this->syncLocations($job, $data['locations'], $request->input('primary_location'));
                $job->industries()->sync($data['industry_ids']);
                $job->skills()->sync($data['skill_ids']);
            });
        } catch (\Throwable $e) {
            report($e); // FIX: không hiển thị $e->getMessage() (có thể chứa SQL) cho người dùng
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật, vui lòng thử lại.');
        }

        return match ($newStatus) {
            'dang_tuyen' => redirect()->route('employer.jobs.active', ['subtab' => 'active'])
                                      ->with('success', 'Đã cập nhật tin tuyển dụng thành công!'),
            'cho_duyet'  => redirect()->route('employer.jobs.active', ['subtab' => 'pending'])
                                      ->with('success', 'Đã cập nhật và gửi tin chờ Admin phê duyệt!'),
            'nhap'       => redirect()->route('employer.jobs.active', ['subtab' => 'draft'])
                                      ->with('success', 'Đã lưu thay đổi vào bản nháp!'),
            // FIX: 'tu_choi' trước đây rơi vào nhánh default và bị chuyển sang trang "Hết hạn"
            'da_dong', 'tu_choi' => redirect()->route('employer.jobs.paused')
                                      ->with('success', 'Đã cập nhật tin tuyển dụng!'),
            'het_han'    => redirect()->route('employer.jobs.expired')
                                      ->with('success', 'Đã cập nhật tin tuyển dụng!'),
        };
    }

     /**
     * Quyết định trạng thái mới khi HR bấm lưu ở trang sửa.
     * Nguyên tắc: không đường nào làm tin lên 'dang_tuyen' nếu nó CHƯA TỪNG được Admin duyệt.
     */
    private function resolveStatusOnUpdate(Job $job, ?string $action, string $deadline, bool $contentChanged): string
    {
        $current  = $job->trang_thai;
        $approved = $job->ngay_dang !== null; // đã từng được Admin duyệt
        $newStatus = $current;

        if ($action === 'nhap') {
            if (!in_array($current, ['nhap', 'tu_choi', 'cho_duyet'], true)) {
                throw ValidationException::withMessages([
                    'action_status' => 'Tin đang hiển thị hoặc đã đóng không thể chuyển về bản nháp. Hãy dùng "Tạm dừng".',
                ]);
            }
            $newStatus = 'nhap';
        } elseif ($action === 'da_dong') {
            // FIX: bản cũ cho 'da_dong' từ mọi trạng thái (kể cả nháp / bị từ chối) rồi 'submit' -> tự lên 'dang_tuyen'
            if ($current !== 'dang_tuyen') {
                throw ValidationException::withMessages(['action_status' => 'Chỉ tin đang tuyển mới có thể tạm dừng.']);
            }
            $newStatus = 'da_dong';
        } elseif ($action === 'submit') {
            $newStatus = ($approved && in_array($current, ['dang_tuyen', 'da_dong', 'het_han'], true))
                ? 'dang_tuyen'   // tin đã từng được duyệt: bật/gia hạn trực tiếp
                : 'cho_duyet';   // còn lại: phải qua Admin
        }

        // FIX: tin đã duyệt mà đổi nội dung thì phải duyệt lại (bản cũ cho sửa tự do rồi giữ nguyên 'dang_tuyen')
        if (self::REVIEW_AGAIN_ON_CONTENT_CHANGE && $approved && $contentChanged
            && in_array($newStatus, ['dang_tuyen', 'da_dong', 'het_han'], true)) {
            $newStatus = 'cho_duyet';
        }

        if (in_array($newStatus, ['dang_tuyen', 'cho_duyet'], true) && !$this->deadlineStillValid($deadline)) {
            throw ValidationException::withMessages(['han_nop_ho_so' => 'Hạn nộp hồ sơ phải từ hôm nay trở đi.']);
        }

        // TODO(gói dịch vụ): nếu $newStatus === 'dang_tuyen' && $current !== 'dang_tuyen' -> kiểm tra so_tin_dang_toi_da

        return $newStatus;
    }

    // ------------------------------------------------------------------
    // Hàm dùng chung
    // ------------------------------------------------------------------

    private function employer(): Employer
    {
        $employer = Auth::user()?->employer;
        abort_if(!$employer, 403, 'Tài khoản của bạn chưa được liên kết với công ty nào.');

        return $employer;
    }

     private function deadlineStillValid(string|\DateTimeInterface $deadline): bool
    {
        return Carbon::parse($deadline)->startOfDay()->gte(today());
    }


    private function validated(Request $request, bool $requireFutureDeadline): array
    {
        $rules = [
            'tieu_de'               => 'required|string|max:255',
            'mo_ta_cong_viec'       => 'required|string',
            'yeu_cau_ung_vien'      => 'nullable|string',
            'quyen_loi'             => 'nullable|string',
            'loai_hinh_lam_viec'    => 'required|in:toan_thoi_gian,ban_thoi_gian,thuc_tap,tu_do,tu_xa',
            'cap_bac'               => 'required|in:thuc_tap_sinh,nhan_vien,truong_nhom,quan_ly,truong_phong,giam_doc',
            'kinh_nghiem_toi_thieu' => 'nullable|integer|min:0|max:50',
            'luong_toi_thieu'       => 'nullable|numeric|min:0|max:999999999999',
            'luong_toi_da'          => 'nullable|numeric|min:0|max:999999999999',
            'so_luong_tuyen'        => 'nullable|integer|min:1|max:9999',
            'han_nop_ho_so'         => array_merge(['required', 'date_format:Y-m-d'],
                                                   $requireFutureDeadline ? ['after_or_equal:today'] : []),
            'industry_ids'          => 'required|array|min:1|max:3',
            'industry_ids.*'        => 'integer|exists:industries,id',
            'skill_ids'             => 'required|array|min:1',
            'skill_ids.*'           => 'integer|exists:skills,id',
            'locations'             => 'required|array|min:1|max:10',
            'locations.*.province_id'      => 'required|integer|exists:provinces,id',
            'locations.*.district_id'      => 'nullable|integer|exists:districts,id',
            'locations.*.dia_chi_chi_tiet' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($v) use ($request) {
            $locations = (array) $request->input('locations', []);

            // Quận/huyện phải thuộc đúng tỉnh đã chọn
            $districtIds = collect($locations)->pluck('district_id')->filter()->unique()->all();
            if ($districtIds) {
                $provinceOf = District::whereIn('id', $districtIds)->pluck('province_id', 'id');
                foreach ($locations as $i => $loc) {
                    if (!empty($loc['district_id'])
                        && (int) ($provinceOf[$loc['district_id']] ?? 0) !== (int) ($loc['province_id'] ?? 0)) {
                        $v->errors()->add("locations.$i.district_id", 'Quận/huyện không thuộc tỉnh/thành đã chọn.');
                    }
                }
            }

            // Không cho trùng địa điểm (tránh kết quả tìm kiếm bị lặp tin khi JOIN job_locations)
            $keys = collect($locations)
                ->map(fn ($l) => ($l['province_id'] ?? '') . '-' . ($l['district_id'] ?? ''))->all();
            if (count($keys) !== count(array_unique($keys))) {
                $v->errors()->add('locations', 'Có địa điểm làm việc bị trùng.');
            }

            // Lương tối đa >= tối thiểu (khi không phải "thỏa thuận")
            if (!$request->boolean('luong_thoa_thuan')) {
                $min = $request->input('luong_toi_thieu');
                $max = $request->input('luong_toi_da');
                if (filled($min) && filled($max) && (float) $max < (float) $min) {
                    $v->errors()->add('luong_toi_da', 'Mức lương tối đa phải lớn hơn hoặc bằng mức tối thiểu.');
                }
            }
        });

        return $validator->validate();
    }

    private function jobAttributes(array $data, Request $request): array
    {
        $thoaThuan = $request->boolean('luong_thoa_thuan'); // boolean() an toàn hơn has() với checkbox/hidden input

        return [
            'tieu_de'               => $data['tieu_de'],
            'mo_ta_cong_viec'       => $this->sanitize($data['mo_ta_cong_viec']),
            'yeu_cau_ung_vien'      => $this->sanitize($data['yeu_cau_ung_vien'] ?? null),
            'quyen_loi'             => $this->sanitize($data['quyen_loi'] ?? null),
            'loai_hinh_lam_viec'    => $data['loai_hinh_lam_viec'],
            'cap_bac'               => $data['cap_bac'],
            // NULL = không yêu cầu kinh nghiệm (đúng với schema; bản cũ store dùng NULL, update lại ép về 0)
            'kinh_nghiem_toi_thieu' => $data['kinh_nghiem_toi_thieu'] ?? null,
            'luong_thoa_thuan'      => $thoaThuan,
            'luong_toi_thieu'       => $thoaThuan ? null : ($data['luong_toi_thieu'] ?? null),
            'luong_toi_da'          => $thoaThuan ? null : ($data['luong_toi_da'] ?? null),
            'so_luong_tuyen'        => $data['so_luong_tuyen'] ?? 1,
            'han_nop_ho_so'         => $data['han_nop_ho_so'],
        ];
    }

    private function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return function_exists('clean') ? clean($html) : strip_tags($html);
    }

    private function makeSlug(string $title): string
    {
        $base = rtrim(Str::limit(Str::slug($title), 200, ''), '-') ?: 'tin-tuyen-dung';

        return $base . '-' . Str::lower(Str::random(6));
    }

    private function syncLocations(Job $job, array $locations, mixed $primaryKey): void
    {
        if (!$job->wasRecentlyCreated) {
            $job->locations()->delete();
        }

        $primary = array_key_exists((string) $primaryKey, $locations)
            ? (string) $primaryKey
            : (string) array_key_first($locations);

        $rows = [];
        foreach ($locations as $index => $loc) {
            $rows[] = [
                'province_id'       => $loc['province_id'],
                'district_id'       => !empty($loc['district_id']) ? $loc['district_id'] : null,
                'dia_chi_chi_tiet'  => $loc['dia_chi_chi_tiet'] ?? null,
                'la_dia_diem_chinh' => (string) $index === $primary,
            ];
        }

        $job->locations()->createMany($rows);
    }

}
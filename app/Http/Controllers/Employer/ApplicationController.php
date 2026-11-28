<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Cv;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    private const TRANSITIONS = [
        'da_nop'        => ['dang_xem_xet', 'phu_hop', 'moi_phong_van', 'tu_choi'],
        'dang_xem_xet'  => ['phu_hop', 'moi_phong_van', 'tu_choi'],
        'phu_hop'       => ['dang_xem_xet', 'moi_phong_van', 'da_tuyen', 'tu_choi'],
        'moi_phong_van' => ['phu_hop', 'da_tuyen', 'tu_choi'],
        'tu_choi'       => ['dang_xem_xet'],
        'da_tuyen'      => [],
    ];

    private const REQUIRE_FULL_CONTACT_STATUSES = ['moi_phong_van', 'da_tuyen'];

    /**
     * FIX: Đĩa lưu CV. 'local' là thư mục PRIVATE (storage/app/private): không có URL công khai.
     * Đĩa 'public' (storage/app/public) được liên kết ra public/storage, nên ai biết đường dẫn file đều
     * tải được qua /storage/... và BỎ QUA hoàn toàn việc khóa thông tin của bạn.
     * Khi đổi sang 'local' phải chuyển file CV cũ sang đó và đổi cả code upload phía ứng viên.
     */
    private const CV_DISK = 'local';

    // ------------------------------------------------------------------
    // Danh sách hồ sơ ứng tuyển
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Application::class);
        $employer = $this->employer();

        $companyJobs = Job::where('company_id', $employer->company_id)
            ->orderByDesc('created_at')
            ->get(['id', 'tieu_de', 'quyen_xem_cv_day_du']);

        $allowedJobIds = $this->resolveFilterJobIds($companyJobs, $request->input('job_id'));

        $statusCounts = $this->statusCounts($allowedJobIds);
        $applications = $this->applicationsQuery($request, $allowedJobIds)
            ->latest('ngay_ung_tuyen')
            ->paginate(12)
            ->withQueryString();

        return view('employer.applications.index', compact(
            'applications',
            'companyJobs',
            'statusCounts'
        ));
    }

    // ------------------------------------------------------------------
    // Chi tiết hồ sơ
    // ------------------------------------------------------------------

    public function show(Application $application)
    {
        // FIX 1: kiểm tra quyền TRƯỚC khi nạp dữ liệu (bản cũ nạp 10 quan hệ rồi mới hỏi "có được xem không").
        // loadMissing('job') = chỉ nạp quan hệ job nếu chưa có; policy cần job để biết hồ sơ thuộc công ty nào.
        $application->loadMissing('job');
        Gate::authorize('manage', $application);

        $employer = $this->employer();

        // FIX 2: đổi trạng thái TRƯỚC khi nạp các quan hệ lớn, nên không cần gọi refresh()
        // (refresh() nạp lại mọi quan hệ đã có và làm mất các quan hệ lồng như candidate.user)
        $this->markAsReviewingIfNew($application, $employer);

        $canViewFull = $application->canViewFullContact();

        // FIX 3: chỉ nạp dữ liệu mà người dùng ĐƯỢC PHÉP xem. Bản cũ luôn nạp email, SĐT và toàn bộ CV
        // rồi để file Blade tự quyết định che hay không -> chỉ cần sai 1 chỗ trong view là lộ dữ liệu.
        // (Tuỳ bạn quyết định thêm trường nào khác của ứng viên cũng bị che khi chưa mở khóa.)
        $application->load([
            'candidate.province:id,ten_tinh',
            'candidate.district:id,ten_quan_huyen',
            'candidate.experiences',
            'candidate.educations',
            'candidate.skills',
            'statusHistories.employer:id,ho_ten',
            $canViewFull ? 'cv' : 'cv:id,ten_cv,loai',
            $canViewFull ? 'candidate.user:id,email,phone,avatar_url' : 'candidate.user:id,avatar_url',
        ]);

        return view('employer.applications.show', compact('application', 'canViewFull'));
    }

    // ------------------------------------------------------------------
    // Cập nhật trạng thái & ghi chú
    // ------------------------------------------------------------------

    public function updateStatus(Request $request, Application $application)
    {
        $application->load('job', 'candidate');
        Gate::authorize('manage', $application);

        $data = $request->validate([
            'trang_thai'     => 'required|in:' . implode(',', array_keys(Application::STATUSES)),
            'ghi_chu_noi_bo' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $application->trang_thai;
        $newStatus = $data['trang_thai'];

        $this->ensureValidTransition($application, $oldStatus, $newStatus);

        $hasNoteInput = $request->exists('ghi_chu_noi_bo');
        $newNote      = $hasNoteInput ? $data['ghi_chu_noi_bo'] : $application->ghi_chu_noi_bo;
        // FIX: chuẩn hoá null và '' về cùng một giá trị, nếu không (null !== '') sẽ bị coi là "có thay đổi"
        $noteChanged  = $hasNoteInput && ((string) $newNote !== (string) $application->ghi_chu_noi_bo);

        if ($oldStatus === $newStatus && !$noteChanged) {
            return back()->with('info', 'Không có thay đổi nào để cập nhật.');
        }

        $employer = $this->employer();

        try {
            DB::transaction(function () use ($application, $employer, $oldStatus, $newStatus, $newNote) {
                // FIX: UPDATE có điều kiện. Hai HR cùng sửa 1 hồ sơ thì chỉ 1 người "thắng":
                // câu lệnh chỉ ghi nếu trạng thái trong DB VẪN là $oldStatus (lúc ta kiểm tra bước chuyển).
                $affected = Application::whereKey($application->id)
                    ->where('trang_thai', $oldStatus)
                    ->update([
                        'trang_thai'     => $newStatus,
                        'ghi_chu_noi_bo' => $newNote,
                    ]);

                if ($affected === 0) {
                    throw ValidationException::withMessages([
                        'trang_thai' => 'Hồ sơ vừa được người khác cập nhật. Vui lòng tải lại trang.',
                    ]);
                }

                $this->recordStatusHistory($application, $oldStatus, $newStatus, $employer, $newNote);

                if ($oldStatus !== $newStatus) {
                    $this->notifyCandidateStatusChanged($application, $newStatus);
                }
            });
        } catch (ValidationException $e) {
            // THỨ TỰ catch QUAN TRỌNG: PHP chạy khối catch đầu tiên khớp kiểu lỗi.
            // ValidationException cũng là một Throwable, nếu để catch (\Throwable) đứng trước thì
            // lỗi nghiệp vụ này sẽ bị nuốt và biến thành thông báo "có lỗi xảy ra" chung chung.
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật hồ sơ, vui lòng thử lại.');
        }

        return back()->with('success', 'Đã cập nhật trạng thái hồ sơ ứng viên!');
    }

    // ------------------------------------------------------------------
    // Xem / tải file CV đính kèm
    // ------------------------------------------------------------------

    public function viewCv(Request $request, Application $application)
    {
        $application->load('job', 'cv');
        Gate::authorize('manage', $application);

        if (!$application->canViewFullContact()) {
            return back()->with('error', 'Tin tuyển dụng này không hỗ trợ xem CV chi tiết. Vui lòng nâng cấp gói dịch vụ để mở khóa.');
        }

        $cv   = $application->cv;
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(self::CV_DISK);

        if (!$cv || $cv->loai !== 'tai_len' || !$cv->file_url || !$disk->exists($cv->file_url)) {
            abort(404, 'Không tìm thấy tệp CV đính kèm của ứng viên.');
        }

        $this->recordCvView($application, $cv, $this->employer());

        if ($request->boolean('download')) {
            $ext  = pathinfo($cv->file_url, PATHINFO_EXTENSION);
            $name = (Str::slug((string) $cv->ten_cv) ?: 'cv') . ($ext !== '' ? ".{$ext}" : '');

            return $disk->download($cv->file_url, $name);
        }

        // 'nosniff': bắt trình duyệt tôn trọng loại file, không tự đoán (phòng file độc hại giả dạng PDF)
        return $disk->response($cv->file_url, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    // ------------------------------------------------------------------
    // Hàm dùng chung (Query, Validate nghiệp vụ, Side-effects)
    // ------------------------------------------------------------------

    private function employer(): Employer
    {
        $employer = Auth::user()?->employer;
        abort_if(!$employer, 403, 'Tài khoản của bạn chưa được liên kết với công ty nào.');

        return $employer;
    }

    private function resolveFilterJobIds(Collection $companyJobs, mixed $requestedJobId): array
    {
        $allIds = $companyJobs->pluck('id')->all();

        if (!filled($requestedJobId)) {
            return $allIds;
        }

        $jobId = (int) $requestedJobId;

        return in_array($jobId, $allIds, true) ? [$jobId] : [];
    }

    private function statusCounts(array $jobIds): Collection
    {
        if ($jobIds === []) {
            return collect();
        }

        return Application::whereIn('job_id', $jobIds)
            ->selectRaw('trang_thai, COUNT(*) AS total')
            ->groupBy('trang_thai')
            ->pluck('total', 'trang_thai');
    }

    private function applicationsQuery(Request $request, array $jobIds): Builder
    {
        $query = Application::with([
            'job:id,company_id,tieu_de,quyen_xem_cv_day_du',
            'candidate:id,user_id,ho_ten,so_nam_kinh_nghiem,trinh_do_hoc_van,muc_luong_mong_muon,province_id,district_id',
            // FIX: danh sách KHÔNG nạp email/SĐT. Bản cũ nạp liên hệ thật của mọi ứng viên, kể cả ở tin đang bị khóa.
            // Nếu danh sách cần hiện liên hệ cho tin đã mở khóa, hãy nạp riêng cho các job đó.
            'candidate.user:id,avatar_url',
            'candidate.province:id,ten_tinh',
            'cv:id,ten_cv,loai',
        ])->whereIn('job_id', $jobIds);

        if ($request->filled('trang_thai') && array_key_exists($request->trang_thai, Application::STATUSES)) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('cv_access') && in_array($request->cv_access, ['0', '1'], true)) {
            $query->whereHas('job', fn ($q) => $q->where('quyen_xem_cv_day_du', (int) $request->cv_access));
        }

        if ($request->filled('q')) {
            $kw = addcslashes(trim($request->q), '%_\\');
            // LƯU Ý: nếu TÊN ứng viên cũng bị che ở tin khóa thì tìm theo tên sẽ làm lộ tên đó
            // (gõ thử tên rồi xem có kết quả hay không). Khi đó chỉ cho tìm theo tên ở các tin đã mở khóa.
            $query->where(function ($q) use ($kw) {
                $q->whereHas('candidate', fn ($c) => $c->where('ho_ten', 'like', "%{$kw}%"))
                  ->orWhereHas('job', fn ($j) => $j->where('tieu_de', 'like', "%{$kw}%"));
            });
        }

        return $query;
    }

    private function ensureValidTransition(Application $application, string $oldStatus, string $newStatus): void
    {
        // FIX: không đổi trạng thái (chỉ sửa ghi chú) thì không cần kiểm tra gì thêm.
        // Bản cũ vẫn chạy kiểm tra "khóa liên hệ" nên HR không thể sửa ghi chú của hồ sơ đang ở 'moi_phong_van'
        // nếu tin bị khóa lại (ví dụ sau khi hạ gói).
        if ($oldStatus === $newStatus) {
            return;
        }

        if (!in_array($newStatus, self::TRANSITIONS[$oldStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'trang_thai' => 'Không thể chuyển hồ sơ từ trạng thái hiện tại sang trạng thái này.',
            ]);
        }

        if (!$application->canViewFullContact() && in_array($newStatus, self::REQUIRE_FULL_CONTACT_STATUSES, true)) {
            throw ValidationException::withMessages([
                'trang_thai' => 'Tin tuyển dụng này đang khóa thông tin liên hệ. Hãy nâng cấp gói để mời phỏng vấn hoặc tuyển ứng viên.',
            ]);
        }
    }

    /** Tự động chuyển 'da_nop' -> 'dang_xem_xet' khi HR mở xem lần đầu. */
    private function markAsReviewingIfNew(Application $application, Employer $employer): void
    {
        if ($application->trang_thai !== 'da_nop') {
            return;
        }

        try {
            $changed = DB::transaction(function () use ($application, $employer) {
                // FIX: UPDATE có điều kiện + kiểm tra số dòng bị đổi. Mở 2 tab cùng lúc thì chỉ 1 request ghi
                // lịch sử (bản cũ ghi trùng 2 dòng lịch sử "đã mở xem hồ sơ").
                $affected = Application::whereKey($application->id)
                    ->where('trang_thai', 'da_nop')
                    ->update(['trang_thai' => 'dang_xem_xet']);

                if ($affected === 1) {
                    $this->recordStatusHistory(
                        $application,
                        'da_nop',
                        'dang_xem_xet',
                        $employer,
                        'Nhà tuyển dụng đã mở xem hồ sơ.'
                    );
                }

                return $affected === 1;
            });

            if ($changed) {
                // Cập nhật bản đang giữ trong bộ nhớ cho khớp DB mà KHÔNG gọi refresh()
                $application->trang_thai = 'dang_xem_xet';
                $application->syncOriginalAttribute('trang_thai');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function recordStatusHistory(
        Application $application,
        ?string $oldStatus,
        string $newStatus,
        Employer $employer,
        ?string $note
    ): void {
        DB::table('application_status_history')->insert([
            'application_id' => $application->id,
            'trang_thai_cu'  => $oldStatus,
            'trang_thai_moi' => $newStatus,
            'employer_id'    => $employer->id,
            'ghi_chu'        => $note !== null ? Str::limit($note, 255, '') : null,
            'created_at'     => now(),
        ]);
    }

    private function notifyCandidateStatusChanged(Application $application, string $newStatus): void
    {
        $userId = $application->candidate?->user_id;
        if (!$userId) {
            return;
        }

        $statusLabel = Application::STATUSES[$newStatus]['label'] ?? $newStatus;

        DB::table('notifications')->insert([
            'user_id'    => $userId,
            'loai'       => 'ho_so_cap_nhat',
            'tieu_de'    => 'Hồ sơ ứng tuyển của bạn đã được cập nhật',
            'noi_dung'   => "Hồ sơ cho vị trí \"{$application->job->tieu_de}\" đã chuyển sang trạng thái: {$statusLabel}.",
            'lien_ket'   => null,
            'da_doc'     => 0,
            'created_at' => now(),
        ]);
    }

    private function recordCvView(Application $application, Cv $cv, Employer $employer): void
    {
        $alreadyLoggedToday = DB::table('cv_views')
            ->where('employer_id', $employer->id)
            ->where('cv_id', $cv->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if ($alreadyLoggedToday) {
            return;
        }

        DB::table('cv_views')->insert([
            'employer_id'      => $employer->id,
            'candidate_id'     => $application->candidate_id,
            'cv_id'            => $cv->id,
            'company_order_id' => $application->job->company_order_id,
            'created_at'       => now(),
        ]);
    }
}
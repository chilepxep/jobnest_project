@extends('layouts.contentNavbarLayout')

@section('title', 'Chi tiết hồ sơ - ' . ($application->candidate?->ho_ten ?? 'Ứng viên'))

@section('content')
@php
$candidate = $application->candidate;
$statuses = \App\Models\Application::STATUSES;
$currentMeta = $statuses[$application->trang_thai] ?? ['label' => $application->trang_thai, 'badge' =>
'bg-label-secondary'];
$eduLabels = \App\Models\Candidate::EDUCATION_LABELS;
$searchLabels = \App\Models\Candidate::JOB_SEARCH_STATUSES;
$skillLevels = [
'co_ban' => 'Cơ bản',
'trung_binh' => 'Trung bình',
'thanh_thao' => 'Thành thạo',
'chuyen_gia' => 'Chuyên gia',
];
$genderLabels = ['nam' => 'Nam', 'nu' => 'Nữ', 'khac' => 'Khác'];
$initials = mb_strtoupper(mb_substr($candidate?->ho_ten ?? 'U', 0, 1));

// Các bước chính trên thanh tiến trình (tách riêng 'tu_choi')
$pipelineSteps = [
'da_nop' => 'Mới nộp',
'dang_xem_xet' => 'Xem xét',
'phu_hop' => 'Phù hợp',
'moi_phong_van' => 'Phỏng vấn',
'da_tuyen' => 'Đã tuyển',
];
$stepKeys = array_keys($pipelineSteps);
$currentStepIndex = array_search($application->trang_thai, $stepKeys, true);
$isRejected = $application->trang_thai === 'tu_choi';
@endphp

<style>
.app-hero-banner {
    height: 92px;
    background: linear-gradient(135deg, #696cff 0%, #5a5fe0 55%, #03c3ec 100%);
    border-radius: 0.5rem 0.5rem 0 0;
}

.app-avatar-wrap {
    margin-top: -46px;
    width: 88px;
    height: 88px;
    border: 4px solid #fff;
    box-shadow: 0 4px 14px rgba(67, 89, 113, 0.16);
}

.app-stat-box {
    background: #f8f9fb;
    border: 1px solid #eceef1;
    border-radius: 0.5rem;
    padding: 0.75rem 0.9rem;
    height: 100%;
}

.app-stepper {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
    gap: 0;
    /* Bỏ gap để đường nối liền mạch giữa tâm 2 điểm */
}

.app-step {
    flex: 1;
    text-align: center;
    position: relative;
}

/* Đường gạch ngang nối từ tâm bước hiện tại sang tâm bước kế tiếp */
.app-step:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 13px;
    /* Căn giữa theo chiều cao 28px của .app-step-dot */
    left: 50%;
    width: 100%;
    height: 2px;
    background: #eceef1;
    z-index: 0;
}

/* Đổi màu xanh cho đoạn nối sau các bước đã hoàn thành */
.app-step.is-done:not(:last-child)::after {
    background: #71dd37;
}

.app-step-dot {
    position: relative;
    z-index: 1;
    /* Nằm đè lên trên đường gạch ngang */
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    background: #eceef1;
    color: #8592a3;
    margin-bottom: 0.35rem;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #eceef1;
}

.app-step.is-done .app-step-dot {
    background: #71dd37;
    color: #fff;
    box-shadow: 0 0 0 2px rgba(113, 221, 55, 0.28);
}

.app-step.is-active .app-step-dot {
    background: #696cff;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(105, 108, 255, 0.22);
}

.app-step-label {
    display: block;
    font-size: 0.75rem;
    color: #8592a3;
    font-weight: 500;
}

.app-step.is-done .app-step-label,
.app-step.is-active .app-step-label {
    color: #566a7f;
    font-weight: 600;
}

.app-timeline {
    position: relative;
    padding-left: 1.5rem;
}

.app-timeline::before {
    content: "";
    position: absolute;
    left: 6px;
    top: 6px;
    bottom: 6px;
    width: 2px;
    background: #e7e9ed;
}

.app-timeline-item {
    position: relative;
    padding-bottom: 1.25rem;
}

.app-timeline-item:last-child {
    padding-bottom: 0;
}

.app-timeline-dot {
    position: absolute;
    left: -1.5rem;
    top: 4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #696cff;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px rgba(105, 108, 255, 0.25);
}

.app-timeline-dot.dot-info {
    background: #03c3ec;
    box-shadow: 0 0 0 2px rgba(3, 195, 236, 0.25);
}

.app-timeline-dot.dot-muted {
    background: #a1acb8;
    box-shadow: 0 0 0 2px rgba(161, 172, 184, 0.25);
}

.app-contact-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0.75rem;
    border-radius: 0.5rem;
    background: #f8f9fb;
    margin-bottom: 0.6rem;
}

.app-contact-icon {
    width: 34px;
    height: 34px;
    border-radius: 0.45rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.app-cv-lock-box {
    background: linear-gradient(180deg, #fffaf0 0%, #fff 100%);
    border: 1px dashed #ffab00;
    border-radius: 0.65rem;
    padding: 1.25rem 1rem;
}

.app-sticky-sidebar {
    position: sticky;
    top: 1.25rem;
}
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Thanh điều hướng trên cùng --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('employer.applications.index') }}" class="btn btn-sm btn-label-secondary">
            <i class="bx bx-arrow-back me-1"></i> Quay lại danh sách hồ sơ
        </a>
        <div class="d-flex align-items-center gap-2 text-muted small">
            <i class="bx bx-time-five"></i>
            <span>Ứng tuyển lúc {{ $application->ngay_ung_tuyen?->format('H:i, d/m/Y') }}</span>
        </div>
    </div>

    {{-- Thông báo Flash --}}
    @foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $type)
    @if (session($key))
    <div class="alert alert-{{ $type }} alert-dismissible fade show shadow-sm" role="alert">
        {{ session($key) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @endforeach

    {{-- Banner cảnh báo khóa liên hệ --}}
    @if (!$canViewFull)
    <div class="alert alert-warning border-warning d-flex flex-wrap justify-content-between align-items-center gap-3 shadow-sm mb-4"
        role="alert">
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-warning p-2 rounded-circle">
                <i class="bx bx-lock-alt fs-4 text-white"></i>
            </span>
            <div>
                <div class="fw-bold">Tin tuyển dụng này đang giới hạn xem thông tin liên hệ</div>
                <div class="small">
                    Bạn vẫn xem được toàn bộ kinh nghiệm, học vấn và kỹ năng. Nâng cấp gói để mở khóa SĐT, Email và tải
                    tệp CV gốc.
                </div>
            </div>
        </div>
        <a href="#" class="btn btn-warning fw-semibold text-nowrap">
            <i class="bx bx-crown me-1"></i> Nâng cấp gói ngay
        </a>
    </div>
    @endif

    <div class="row g-4">
        {{-- ==================== CỘT TRÁI: HỒ SƠ CHUYÊN MÔN ==================== --}}
        <div class="col-lg-8">
            {{-- 1. Hero Profile Card --}}
            <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                <div class="app-hero-banner"></div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            @if ($candidate?->user?->avatar_url)
                            <img src="{{ $candidate->user->avatar_url }}" alt="Avatar"
                                class="rounded-circle object-fit-cover app-avatar-wrap bg-white">
                            @else
                            <div
                                class="rounded-circle app-avatar-wrap bg-primary text-white d-flex align-items-center justify-content-center fs-2 fw-bold">
                                {{ $initials }}
                            </div>
                            @endif

                            <div class="pt-2">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h4 class="mb-0 fw-bold">{{ $candidate?->ho_ten ?? 'Ứng viên' }}</h4>
                                    <span class="badge {{ $currentMeta['badge'] }}">{{ $currentMeta['label'] }}</span>
                                    @if ($candidate?->trang_thai_tim_viec)
                                    <span class="badge bg-label-info">
                                        <i
                                            class="bx bx-radar me-1"></i>{{ $searchLabels[$candidate->trang_thai_tim_viec] ?? $candidate->trang_thai_tim_viec }}
                                    </span>
                                    @endif
                                </div>
                                <div class="text-muted small">
                                    Ứng tuyển:
                                    <span class="fw-semibold text-primary">{{ $application->job?->tieu_de }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4 ô thông số nhanh --}}
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="app-stat-box">
                                <small class="text-muted d-block mb-1"><i
                                        class="bx bx-briefcase me-1 text-primary"></i>Kinh nghiệm</small>
                                <div class="fw-bold text-heading">
                                    {{ $candidate?->so_nam_kinh_nghiem !== null ? rtrim(rtrim(number_format($candidate->so_nam_kinh_nghiem, 1), '0'), '.') . ' năm' : 'Chưa rõ' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="app-stat-box">
                                <small class="text-muted d-block mb-1"><i
                                        class="bx bx-money me-1 text-success"></i>Lương mong muốn</small>
                                <div class="fw-bold text-heading">
                                    {{ $candidate?->muc_luong_mong_muon ? number_format($candidate->muc_luong_mong_muon, 0, ',', '.') . ' ₫' : 'Thỏa thuận' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="app-stat-box">
                                <small class="text-muted d-block mb-1"><i class="bx bx-book-open me-1 text-info"></i>Học
                                    vấn</small>
                                <div class="fw-bold text-heading">
                                    {{ $eduLabels[$candidate?->trinh_do_hoc_van] ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="app-stat-box">
                                <small class="text-muted d-block mb-1"><i class="bx bx-user me-1 text-warning"></i>Cá
                                    nhân</small>
                                <div class="fw-bold text-heading">
                                    {{ $genderLabels[$candidate?->gioi_tinh] ?? '—' }}
                                    @if ($candidate?->ngay_sinh)
                                    <span class="fw-normal text-muted small">({{ $candidate->ngay_sinh->age }}
                                        tuổi)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Thanh tiến trình tuyển dụng --}}
                    <div class="pt-3 border-top">
                        @if ($isRejected)
                        <div class="d-flex align-items-center gap-2 text-danger small fw-semibold">
                            <i class="bx bx-x-circle fs-5"></i>
                            <span>Hồ sơ này hiện đang ở trạng thái Từ chối (bạn vẫn có thể chuyển lại "Đang xem xét" ở
                                cột bên phải).</span>
                        </div>
                        @else
                        <div class="app-stepper">
                            @foreach ($pipelineSteps as $code => $label)
                            @php
                            $idx = array_search($code, $stepKeys, true);
                            $isDone = $currentStepIndex !== false && $idx < $currentStepIndex;
                                $isActive=$currentStepIndex !==false && $idx===$currentStepIndex; @endphp <div
                                class="app-step {{ $isDone ? 'is-done' : '' }} {{ $isActive ? 'is-active' : '' }}">
                                <span class="app-step-dot">
                                    @if ($isDone)
                                    <i class="bx bx-check"></i>
                                    @else
                                    {{ $idx + 1 }}
                                    @endif
                                </span>
                                <span class="app-step-label">{{ $label }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Thư giới thiệu & Mục tiêu nghề nghiệp --}}
        @if ($application->thu_gioi_thieu || $candidate?->muc_tieu_nghe_nghiep)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                @if ($application->thu_gioi_thieu)
                <div class="mb-3">
                    <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-label-primary p-1"><i class="bx bx-message-square-detail"></i></span>
                        Thư giới thiệu của ứng viên
                    </h6>
                    <div class="p-3 rounded bg-light border-start border-3 border-primary text-body small"
                        style="white-space: pre-line; line-height: 1.65;">{{ $application->thu_gioi_thieu }}</div>
                </div>
                @endif

                @if ($candidate?->muc_tieu_nghe_nghiep)
                <div class="mb-0">
                    <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-label-info p-1"><i class="bx bx-target-lock"></i></span>
                        Mục tiêu nghề nghiệp
                    </h6>
                    <p class="mb-0 text-body small" style="white-space: pre-line; line-height: 1.65;">
                        {{ $candidate->muc_tieu_nghe_nghiep }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- 3. Kinh nghiệm làm việc (Timeline) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="bx bx-briefcase-alt-2 me-2 text-primary"></i>Kinh nghiệm làm việc
                </h5>
                <span class="badge bg-label-secondary">{{ ($candidate?->experiences ?? collect())->count() }} mục</span>
            </div>
            <div class="card-body pt-4">
                @if (($candidate?->experiences ?? collect())->isNotEmpty())
                <div class="app-timeline">
                    @foreach ($candidate->experiences as $exp)
                    <div class="app-timeline-item">
                        <span class="app-timeline-dot"></span>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                            <h6 class="fw-bold mb-0">{{ $exp->vi_tri_cong_viec }}</h6>
                            <span class="badge bg-label-primary">
                                {{ $exp->tu_thang?->format('m/Y') }} –
                                {{ $exp->dang_lam_viec ? 'Hiện tại' : ($exp->den_thang?->format('m/Y') ?? '—') }}
                            </span>
                        </div>
                        <div class="text-primary small fw-semibold mb-2">
                            <i class="bx bx-buildings me-1"></i>{{ $exp->ten_cong_ty }}
                        </div>
                        @if ($exp->mo_ta_cong_viec)
                        <p class="mb-0 small text-muted" style="white-space: pre-line; line-height: 1.6;">
                            {{ $exp->mo_ta_cong_viec }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-3 small">
                    <i class="bx bx-briefcase fs-3 d-block mb-1 opacity-50"></i>
                    Ứng viên chưa cập nhật kinh nghiệm làm việc.
                </div>
                @endif
            </div>
        </div>

        {{-- 4. Học vấn (Timeline) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="bx bx-book-open me-2 text-info"></i>Học vấn
                </h5>
                <span class="badge bg-label-secondary">{{ ($candidate?->educations ?? collect())->count() }} mục</span>
            </div>
            <div class="card-body pt-4">
                @if (($candidate?->educations ?? collect())->isNotEmpty())
                <div class="app-timeline">
                    @foreach ($candidate->educations as $edu)
                    <div class="app-timeline-item">
                        <span class="app-timeline-dot dot-info"></span>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                            <h6 class="fw-bold mb-0">{{ $edu->ten_truong }}</h6>
                            <span class="badge bg-label-info">
                                {{ $edu->tu_nam }} – {{ $edu->dang_hoc ? 'Hiện tại' : ($edu->den_nam ?? '—') }}
                            </span>
                        </div>
                        <div class="small fw-medium text-muted mb-1">
                            {{ collect([$edu->bang_cap, $edu->chuyen_nganh])->filter()->implode(' • ') }}
                        </div>
                        @if ($edu->mo_ta)
                        <p class="mb-0 small text-muted" style="white-space: pre-line;">{{ $edu->mo_ta }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-3 small">
                    <i class="bx bx-book-open fs-3 d-block mb-1 opacity-50"></i>
                    Ứng viên chưa cập nhật học vấn.
                </div>
                @endif
            </div>
        </div>

        {{-- 5. Kỹ năng --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0 fw-bold">
                    <i class="bx bx-code-alt me-2 text-success"></i>Kỹ năng chuyên môn
                </h5>
            </div>
            <div class="card-body pt-3">
                @forelse ($candidate?->skills ?? [] as $skill)
                <span class="badge bg-label-primary rounded-pill me-1 mb-2 py-2 px-3">
                    {{ $skill->ten_ky_nang }}
                    @if ($skill->pivot?->muc_do)
                    <span class="opacity-75 fw-normal ms-1">
                        • {{ $skillLevels[$skill->pivot->muc_do] ?? $skill->pivot->muc_do }}
                    </span>
                    @endif
                </span>
                @empty
                <p class="text-muted small mb-0">Ứng viên chưa cập nhật kỹ năng.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ==================== CỘT PHẢI (STICKY): LIÊN HỆ, CV & XỬ LÝ ==================== --}}
    <div class="col-lg-4">
        <div class="app-sticky-sidebar">
            {{-- 1. Thông tin liên hệ & File CV --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Thông tin liên hệ &amp; CV</h6>
                    @if ($canViewFull)
                    <span class="badge bg-label-success"><i class="bx bx-check-shield me-1"></i>Full CV</span>
                    @else
                    <span class="badge bg-label-warning"><i class="bx bx-lock-alt me-1"></i>Đã ẩn</span>
                    @endif
                </div>
                <div class="card-body pt-3">
                    <div class="app-contact-row">
                        <span class="app-contact-icon bg-label-primary text-primary">
                            <i class="bx bx-envelope"></i>
                        </span>
                        <div class="overflow-hidden">
                            <small class="text-muted d-block">Email</small>
                            <div
                                class="text-truncate small {{ !$canViewFull ? 'text-muted fst-italic' : 'fw-semibold' }}">
                                {{ $application->display_email }}
                            </div>
                        </div>
                    </div>

                    <div class="app-contact-row">
                        <span class="app-contact-icon bg-label-success text-success">
                            <i class="bx bx-phone"></i>
                        </span>
                        <div class="overflow-hidden">
                            <small class="text-muted d-block">Số điện thoại</small>
                            <div
                                class="text-truncate small {{ !$canViewFull ? 'text-muted fst-italic' : 'fw-semibold' }}">
                                {{ $application->display_phone }}
                            </div>
                        </div>
                    </div>

                    <div class="app-contact-row mb-3">
                        <span class="app-contact-icon bg-label-info text-info">
                            <i class="bx bx-map"></i>
                        </span>
                        <div class="overflow-hidden">
                            <small class="text-muted d-block">Địa chỉ</small>
                            <div class="small {{ !$canViewFull ? 'text-muted fst-italic' : 'fw-medium' }}">
                                {{ $application->display_address }}
                            </div>
                        </div>
                    </div>

                    {{-- Khu vực tệp CV --}}
                    @if ($canViewFull)
                    @if ($application->cv && $application->cv->loai === 'tai_len')
                    <div class="p-3 rounded bg-lighter border mb-2">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bxs-file-pdf fs-2 text-danger"></i>
                            <div class="overflow-hidden">
                                <div class="small fw-semibold text-truncate">{{ $application->cv->ten_cv }}</div>
                                <small class="text-muted">Tệp CV đính kèm</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('employer.applications.cv', $application) }}" target="_blank"
                                class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bx bx-show me-1"></i> Xem CV
                            </a>
                            <a href="{{ route('employer.applications.cv', ['application' => $application, 'download' => 1]) }}"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-download"></i>
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="p-3 rounded bg-light small text-muted">
                        CV tạo trên hệ thống: <strong>{{ $application->cv?->ten_cv ?? '—' }}</strong>
                    </div>
                    @endif
                    @else
                    <div class="app-cv-lock-box text-center">
                        <span class="badge bg-label-warning p-2 rounded-circle mb-2">
                            <i class="bx bx-lock-alt fs-4"></i>
                        </span>
                        <div class="fw-bold small mb-1">Tệp CV đã được bảo vệ</div>
                        <div class="small text-muted text-truncate mb-3">
                            {{ $application->cv?->ten_cv ?? 'CV ứng viên' }}
                        </div>
                        <a href="#" class="btn btn-warning btn-sm w-100 fw-semibold">
                            <i class="bx bx-crown me-1"></i> Mở khóa bằng gói dịch vụ
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            {{-- 2. Form cập nhật trạng thái & Ghi chú --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0 fw-bold">Xử lý hồ sơ</h6>
                </div>
                <div class="card-body pt-3">
                    <form method="POST" action="{{ route('employer.applications.update-status', $application) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="trang_thai" class="form-label small fw-semibold">Trạng thái tuyển dụng</label>
                            <select id="trang_thai" name="trang_thai"
                                class="form-select @error('trang_thai') is-invalid @enderror">
                                @foreach ($statuses as $code => $meta)
                                <option value="{{ $code }}" @selected(old('trang_thai', $application->trang_thai) ===
                                    $code)>
                                    {{ $meta['label'] }}
                                    @if (!$canViewFull && in_array($code, ['moi_phong_van', 'da_tuyen'], true))
                                    (Cần mở khóa CV)
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            @error('trang_thai')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="ghi_chu_noi_bo" class="form-label small fw-semibold">Ghi chú nội bộ</label>
                            <textarea id="ghi_chu_noi_bo" name="ghi_chu_noi_bo" rows="3"
                                class="form-control @error('ghi_chu_noi_bo') is-invalid @enderror"
                                placeholder="VD: Hẹn phỏng vấn 14h thứ 5, đề xuất mức lương...">{{ old('ghi_chu_noi_bo', $application->ghi_chu_noi_bo) }}</textarea>
                            @error('ghi_chu_noi_bo')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bx bx-save me-1"></i> Lưu cập nhật
                        </button>
                    </form>
                </div>
            </div>

            {{-- 3. Lịch sử thay đổi trạng thái (Compact Timeline) --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Lịch sử xử lý</h6>
                    <span class="badge bg-label-secondary">{{ $application->statusHistories->count() }}</span>
                </div>
                <div class="card-body pt-3">
                    @if ($application->statusHistories->isNotEmpty())
                    <div class="app-timeline">
                        @foreach ($application->statusHistories as $history)
                        @php
                        $newLabel = $statuses[$history->trang_thai_moi]['label'] ?? $history->trang_thai_moi;
                        $oldLabel = $history->trang_thai_cu ? ($statuses[$history->trang_thai_cu]['label'] ??
                        $history->trang_thai_cu) : null;
                        @endphp
                        <div class="app-timeline-item">
                            <span class="app-timeline-dot dot-muted"></span>
                            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                                <span class="small fw-semibold">
                                    @if ($oldLabel && $oldLabel !== $newLabel)
                                    {{ $oldLabel }} &rarr; {{ $newLabel }}
                                    @else
                                    {{ $newLabel }}
                                    @endif
                                </span>
                                <small class="text-muted text-nowrap" style="font-size: 0.72rem;">
                                    {{ $history->created_at?->format('d/m H:i') }}
                                </small>
                            </div>
                            @if ($history->ghi_chu)
                            <p class="small text-muted mb-1">{{ $history->ghi_chu }}</p>
                            @endif
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="bx bx-user me-1"></i>{{ $history->employer?->ho_ten ?? 'Hệ thống' }}
                            </small>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted small mb-0">Chưa có lịch sử cập nhật.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
@extends('layouts.contentNavbarLayout')

@php
$tab = $tab ?? request()->route('tab') ?? request('tab') ?? 'active';
$subtab = $subtab ?? request('subtab', $tab === 'active' ? 'active' : null);
$jobs = $jobs ?? collect();
$counts = $counts ?? [];

$pageConfig = [
'active' => [
'title' => 'Việc làm đang hoạt động',
'subtitle' => 'Các tin đang hiển thị tìm ứng viên hoặc đang chờ Admin phê duyệt.',
'icon' => 'bx-check-shield',
'color' => 'success',
],
'paused' => [
'title' => 'Việc làm đã tạm dừng & Nháp',
'subtitle' => 'Các tin đang tạm khóa, lưu nháp hoặc cần chỉnh sửa theo góp ý của Admin.',
'icon' => 'bx-pause-circle',
'color' => 'warning',
],
'expired' => [
'title' => 'Việc làm đã hết hạn',
'subtitle' => 'Các tin đã qua hạn nộp hồ sơ. Bấm Sửa để gia hạn thêm thời gian tuyển dụng.',
'icon' => 'bx-calendar-x',
'color' => 'secondary',
],
];
$current = $pageConfig[$tab] ?? $pageConfig['active'];

$workTypeLabels = [
'toan_thoi_gian' => 'Toàn thời gian',
'ban_thoi_gian' => 'Bán thời gian',
'thuc_tap' => 'Thực tập',
'tu_do' => 'Freelance',
'tu_xa' => 'Remote',
];

$levelLabels = [
'thuc_tap_sinh' => 'Thực tập sinh',
'nhan_vien' => 'Nhân viên',
'truong_nhom' => 'Trưởng nhóm',
'quan_ly' => 'Quản lý',
'truong_phong' => 'Trưởng phòng',
'giam_doc' => 'Giám đốc',
];

$statusBadges = [
'dang_tuyen' => ['Đang tuyển', 'success', 'bx-broadcast'],
'cho_duyet' => ['Chờ duyệt', 'info', 'bx-time-five'],
'nhap' => ['Bản nháp', 'secondary', 'bx-edit-alt'],
'da_dong' => ['Đã tạm dừng', 'warning', 'bx-pause'],
'tu_choi' => ['Bị từ chối', 'danger', 'bx-x-circle'],
'het_han' => ['Đã hết hạn', 'dark', 'bx-calendar-exclamation'],
];

// Định dạng lương: 10.0 -> 10, 12.5 -> 12,5
$m = fn($v) => rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',');

$totalLabel = $tab === 'active'
? match ($subtab) {
'pending' => $counts['active_pending'] ?? 0,
'draft' => $counts['active_draft'] ?? 0,
default => $counts['active_running'] ?? 0,
}
: ($counts[$tab] ?? (method_exists($jobs, 'total') ? $jobs->total() : $jobs->count()));
@endphp

@section('title', $current['title'])

@section('page-style')
<style>
/* ===== Banner ===== */
.list-hero {
    background: linear-gradient(135deg, #696cff 0%, #8f91ff 100%);
    border-radius: .5rem;
    color: #fff;
    padding: 1.5rem 1.75rem;
    position: relative;
    overflow: hidden;
}

.list-hero::after {
    content: "";
    position: absolute;
    right: -40px;
    top: -50px;
    width: 190px;
    height: 190px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .12);
}

.list-hero h4,
.list-hero p {
    color: #fff;
    position: relative;
    z-index: 1;
}

.list-hero .btn {
    position: relative;
    z-index: 1;
}

/* ===== Tabs dạng viên thuốc ===== */
.pill-tabs {
    display: inline-flex;
    gap: .35rem;
    padding: .3rem;
    background: #f1f1f8;
    border-radius: .5rem;
}

.pill-tabs .pill-tab {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem 1rem;
    border-radius: .4rem;
    font-weight: 600;
    font-size: .88rem;
    color: #697a8d;
    text-decoration: none;
    transition: all .15s;
}

.pill-tabs .pill-tab:hover {
    color: #696cff;
}

.pill-tabs .pill-tab.active {
    background: #fff;
    color: #696cff;
    box-shadow: 0 2px 6px rgba(105, 108, 255, .2);
}

/* ===== Bảng ===== */
.job-table thead th {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #8592a3;
    background: #f8f8fb;
    border-bottom: 1px solid #e9e9f0;
    white-space: nowrap;
}

.job-table> :not(caption)>*>* {
    padding: 1.05rem 1rem;
    vertical-align: middle;
}

.job-table tbody tr {
    transition: background .15s;
}

.job-table tbody tr:hover {
    background: #fafaff;
}

.job-title-link {
    font-weight: 600;
    color: #32475c;
    font-size: .97rem;
    text-decoration: none;
    line-height: 1.35;
}

.job-title-link:hover {
    color: #696cff;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .76rem;
    color: #697a8d;
    background: #f5f5f9;
    border-radius: 2rem;
    padding: .15rem .6rem;
}

.salary-text {
    font-weight: 700;
    color: #28a745;
    font-size: .95rem;
}

.stat-box {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    min-width: 54px;
    padding: .35rem .5rem;
    border-radius: .4rem;
    background: #f5f5f9;
}

.stat-box b {
    font-size: .95rem;
    line-height: 1.1;
}

.stat-box small {
    font-size: .68rem;
    color: #8592a3;
}

.stat-box.primary {
    background: #e7e7ff;
}

.stat-box.primary b {
    color: #696cff;
}

.reject-reason-box {
    background: #fff0ee;
    border-left: 3px solid #ff3e1d;
    color: #b42318;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: .78rem;
    margin-top: 8px;
    white-space: normal;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.btn-action {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* ===== Filter bar ===== */
.filter-bar {
    background: #fafafd;
    border-top: 1px solid #eceef1;
    border-bottom: 1px solid #eceef1;
}

/* ===== Modal ===== */
.info-tile {
    background: #f8f8fb;
    border-radius: .5rem;
    padding: .75rem 1rem;
    height: 100%;
}

.info-tile small {
    display: block;
    color: #8592a3;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: .15rem;
}

.detail-section h6 {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-weight: 700;
    color: #696cff;
    margin-bottom: .6rem;
}

.detail-section h6::before {
    content: "";
    width: 4px;
    height: 16px;
    border-radius: 2px;
    background: #696cff;
}

.detail-content {
    line-height: 1.7;
    color: #566a7f;
}
</style>
@endsection


@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Banner -->
    <div class="list-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <small class="text-white-50 text-uppercase fw-semibold">Quản lý việc làm</small>
            <h4 class="fw-bold mb-1 mt-1">{{ $current['title'] }}</h4>
            <p class="mb-0 opacity-75">{{ $current['subtitle'] }}</p>
        </div>
        <a href="{{ route('employer.jobs.create') }}" class="btn btn-light fw-bold shadow-sm">
            <i class="bx bx-plus-circle me-1 text-success"></i> Đăng tin tuyển dụng
        </a>
    </div>

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible" role="alert">
        <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card overflow-hidden">

        <!-- Đầu card: Tabs hoặc tiêu đề -->
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
            @if ($tab === 'active')
            <div class="pill-tabs">
                <a class="pill-tab {{ $subtab === 'active' ? 'active' : '' }}"
                    href="{{ route('employer.jobs.active', ['subtab' => 'active']) }}">
                    <i class="bx bx-broadcast"></i> Đang hoạt động
                    <span class="badge rounded-pill {{ $subtab === 'active' ? 'bg-primary' : 'bg-label-secondary' }}">
                        {{ $counts['active_running'] ?? 0 }}
                    </span>
                </a>
                <a class="pill-tab {{ $subtab === 'pending' ? 'active' : '' }}"
                    href="{{ route('employer.jobs.active', ['subtab' => 'pending']) }}">
                    <i class="bx bx-time-five"></i> Chờ duyệt
                    <span class="badge rounded-pill {{ $subtab === 'pending' ? 'bg-primary' : 'bg-label-secondary' }}">
                        {{ $counts['active_pending'] ?? 0 }}
                    </span>
                </a>
                <a class="pill-tab {{ $subtab === 'draft' ? 'active' : '' }}"
                    href="{{ route('employer.jobs.active', ['subtab' => 'draft']) }}">
                    <i class="bx bx-edit-alt"></i> Bản nháp
                    <span class="badge rounded-pill {{ $subtab === 'draft' ? 'bg-primary' : 'bg-label-secondary' }}">
                        {{ $counts['active_draft'] ?? 0 }}
                    </span>
                </a>
            </div>
            @else
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm">
                    <span class="avatar-initial rounded bg-label-{{ $current['color'] }}">
                        <i class="bx {{ $current['icon'] }}"></i>
                    </span>
                </span>
                <h6 class="mb-0 fw-bold">Danh sách tin</h6>
            </div>
            @endif

            <span class="badge bg-label-{{ $current['color'] }} fs-6 px-3 py-2">
                Tổng cộng: {{ $totalLabel }} tin
            </span>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-bar px-4 py-3">
            <form method="GET" class="row g-2 align-items-center">
                @if ($tab === 'active' && in_array($subtab, ['pending', 'draft']))
                <input type="hidden" name="subtab" value="{{ $subtab }}">
                @endif

                <div class="col-12 col-md-5">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                            placeholder="Tìm theo tiêu đề tin tuyển dụng...">
                    </div>
                </div>

                @if ($tab === 'paused')
                <div class="col-6 col-md-3">
                    <select name="trang_thai" class="form-select" onchange="this.form.submit()">
                        <option value="">Mọi trạng thái</option>
                        <option value="da_dong" @selected(request('trang_thai')==='da_dong' )>Đã tạm dừng</option>
                        <option value="tu_choi" @selected(request('trang_thai')==='tu_choi' )>Bị từ chối</option>
                    </select>
                </div>
                @endif

                <div class="col-6 col-md-2">
                    <select name="loai_hinh" class="form-select" onchange="this.form.submit()">
                        <option value="">Mọi hình thức</option>
                        @foreach ($workTypeLabels as $val => $lbl)
                        <option value="{{ $val }}" @selected(request('loai_hinh')===$val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bx bx-filter-alt me-1"></i> Lọc
                    </button>
                    @if (request()->hasAny(['q', 'trang_thai', 'loai_hinh']))
                    <a href="{{ url()->current() . ($tab === 'active' && $subtab !== 'active' ? '?subtab=' . $subtab : '') }}"
                        class="btn btn-label-secondary btn-icon" title="Xóa bộ lọc">
                        <i class="bx bx-reset"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Bảng -->
        <div class="table-responsive text-nowrap">
            <table class="table job-table mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 300px;">Tin tuyển dụng</th>
                        <th>Mức lương</th>
                        <th class="text-center">Tương tác</th>
                        <th>Thời hạn</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                    @php
                    $primaryLoc = $job->locations->firstWhere('la_dia_diem_chinh', 1) ?? $job->locations->first();
                    $provinceName = $primaryLoc?->province?->name ?? ($primaryLoc?->province?->ten_tinh ?? 'Chưa cập
                    nhật');
                    $extraLocCount = max(0, $job->locations->count() - 1);

                    $badge = $statusBadges[$job->trang_thai] ?? ['Không rõ', 'secondary', 'bx-info-circle'];
                    $deadline = \Carbon\Carbon::parse($job->han_nop_ho_so)->startOfDay();
                    $daysLeft = (int) now()->startOfDay()->diffInDays($deadline, false);
                    @endphp
                    <tr>
                        <!-- 1. Tin tuyển dụng -->
                        <td class="text-wrap" style="max-width: 360px;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="avatar avatar-md flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-{{ $badge[1] }}">
                                        <i class="bx bx-briefcase-alt-2"></i>
                                    </span>
                                </span>
                                <div>
                                    <a href="javascript:void(0);" class="job-title-link d-block mb-2"
                                        data-bs-toggle="modal" data-bs-target="#jobPreviewModal_{{ $job->id }}">
                                        {{ $job->tieu_de }}
                                    </a>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="meta-chip">
                                            <i class="bx bx-medal text-primary"></i>
                                            {{ $levelLabels[$job->cap_bac] ?? $job->cap_bac }}
                                        </span>
                                        <span class="meta-chip">
                                            <i class="bx bx-time-five text-info"></i>
                                            {{ $workTypeLabels[$job->loai_hinh_lam_viec] ?? $job->loai_hinh_lam_viec }}
                                        </span>
                                        <span class="meta-chip">
                                            <i class="bx bx-map text-danger"></i>
                                            {{ $provinceName }}
                                            @if ($extraLocCount > 0)
                                            <b>+{{ $extraLocCount }}</b>
                                            @endif
                                        </span>
                                    </div>

                                    @if ($job->trang_thai === 'tu_choi' && $job->ly_do_tu_choi)
                                    <div class="reject-reason-box">
                                        <strong><i class="bx bx-error me-1"></i>Lý do từ chối:</strong>
                                        {{ $job->ly_do_tu_choi }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- 2. Lương -->
                        <td>
                            <div class="salary-text mb-1">
                                @if ($job->luong_thoa_thuan)
                                Thỏa thuận
                                @elseif ($job->luong_toi_thieu && $job->luong_toi_da)
                                {{ $m($job->luong_toi_thieu) }} - {{ $m($job->luong_toi_da) }} triệu
                                @elseif ($job->luong_toi_thieu)
                                Từ {{ $m($job->luong_toi_thieu) }} triệu
                                @elseif ($job->luong_toi_da)
                                Đến {{ $m($job->luong_toi_da) }} triệu
                                @else
                                Thỏa thuận
                                @endif
                            </div>
                            <div class="small text-muted">
                                <i class="bx bx-group"></i> {{ $job->so_luong_tuyen }} người
                                <span class="mx-1">•</span>
                                <i class="bx bx-trophy"></i>
                                {{ $job->kinh_nghiem_toi_thieu ? $job->kinh_nghiem_toi_thieu . ' năm KN' : 'Không YC KN' }}
                            </div>
                        </td>

                        <!-- 3. Tương tác -->
                        <td class="text-center">
                            <div class="d-inline-flex gap-2">
                                <div class="stat-box primary" title="Số hồ sơ ứng tuyển">
                                    <b>{{ $job->applications_count ?? $job->luot_ung_tuyen ?? 0 }}</b>
                                    <small>Ứng viên</small>
                                </div>
                                <div class="stat-box" title="Lượt xem tin">
                                    <b>{{ number_format($job->luot_xem ?? 0) }}</b>
                                    <small>Lượt xem</small>
                                </div>
                            </div>
                        </td>

                        <!-- 4. Thời hạn -->
                        <td>
                            <div class="fw-medium">
                                <i class="bx bx-calendar me-1 text-muted"></i>{{ $deadline->format('d/m/Y') }}
                            </div>
                            <div class="small mt-1">
                                @if ($daysLeft > 3)
                                <span class="badge bg-label-success">Còn {{ $daysLeft }} ngày</span>
                                @elseif ($daysLeft >= 0)
                                <span class="badge bg-label-warning">Sắp hết hạn ({{ $daysLeft }} ngày)</span>
                                @else
                                <span class="badge bg-label-danger">Quá hạn {{ abs($daysLeft) }} ngày</span>
                                @endif
                            </div>
                        </td>

                        <!-- 5. Trạng thái -->
                        <td>
                            <span
                                class="badge bg-label-{{ $badge[1] }} d-inline-flex align-items-center gap-1 px-3 py-2">
                                <i class="bx {{ $badge[2] }}"></i> {{ $badge[0] }}
                            </span>
                        </td>

                        <!-- 6. Thao tác -->
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-action btn-label-info" data-bs-toggle="modal"
                                    data-bs-target="#jobPreviewModal_{{ $job->id }}" title="Xem chi tiết">
                                    <i class="bx bx-show"></i>
                                </button>
                                <a href="{{ route('employer.jobs.edit', $job->id) }}"
                                    class="btn btn-action btn-label-primary" title="Chỉnh sửa tin">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <div class="dropdown">
                                    <button type="button" class="btn btn-action btn-label-secondary"
                                        data-bs-toggle="dropdown" aria-expanded="false" title="Thêm">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if ($job->trang_thai === 'dang_tuyen')
                                        <form action="{{ route('employer.jobs.status', $job->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="da_dong">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx bx-pause me-2 text-warning"></i> Tạm dừng nhận hồ sơ
                                            </button>
                                        </form>
                                        @elseif ($job->trang_thai === 'da_dong' && $daysLeft >= 0)
                                        <form action="{{ route('employer.jobs.status', $job->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="dang_tuyen">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx bx-play me-2 text-success"></i> Kích hoạt lại tin
                                            </button>
                                        </form>
                                        @elseif ($job->trang_thai === 'nhap')
                                        <form action="{{ route('employer.jobs.status', $job->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="trang_thai" value="cho_duyet">
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx bx-paper-plane me-2 text-success"></i> Gửi duyệt ngay
                                            </button>
                                        </form>
                                        @endif

                                        <form action="{{ route('employer.jobs.destroy', $job->id) }}" method="POST"
                                            class="form-delete-job">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bx bx-trash me-2"></i> Xóa tin
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <span class="avatar avatar-xl mb-3">
                                <span class="avatar-initial rounded-circle bg-label-{{ $current['color'] }}">
                                    <i class="bx {{ $current['icon'] }} fs-1"></i>
                                </span>
                            </span>
                            <h6 class="fw-bold mb-1">Chưa có tin tuyển dụng nào trong mục này</h6>
                            <p class="text-muted small mb-3">
                                Tạo tin tuyển dụng mới để bắt đầu thu hút ứng viên ngay hôm nay.
                            </p>

                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        @if (method_exists($jobs, 'hasPages') && $jobs->hasPages())
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">
                Hiển thị {{ $jobs->firstItem() }} - {{ $jobs->lastItem() }} / {{ $jobs->total() }} tin
            </small>
            {{ $jobs->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <!-- ========== MODAL XEM NHANH ========== -->
    @foreach ($jobs as $job)
    @php $b = $statusBadges[$job->trang_thai] ?? ['Không rõ', 'secondary', 'bx-info-circle']; @endphp
    <div class="modal fade" id="jobPreviewModal_{{ $job->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">{{ $job->tieu_de }}</h5>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-label-{{ $b[1] }}">
                                <i class="bx {{ $b[2] }} me-1"></i>{{ $b[0] }}
                            </span>
                            <small class="text-muted">Mã tin #{{ $job->id }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="info-tile">
                                <small>Cấp bậc</small>
                                <strong>{{ $levelLabels[$job->cap_bac] ?? $job->cap_bac }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="info-tile">
                                <small>Hình thức</small>
                                <strong>{{ $workTypeLabels[$job->loai_hinh_lam_viec] ?? $job->loai_hinh_lam_viec }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="info-tile">
                                <small>Kinh nghiệm</small>
                                <strong>{{ $job->kinh_nghiem_toi_thieu ? $job->kinh_nghiem_toi_thieu . ' năm' : 'Không yêu cầu' }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="info-tile">
                                <small>Hạn nộp hồ sơ</small>
                                <strong>{{ \Carbon\Carbon::parse($job->han_nop_ho_so)->format('d/m/Y') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section mb-4">
                        <h6>Mô tả công việc</h6>
                        <div class="detail-content">{!! $job->mo_ta_cong_viec !!}</div>
                    </div>

                    @if ($job->yeu_cau_ung_vien)
                    <div class="detail-section mb-4">
                        <h6>Yêu cầu ứng viên</h6>
                        <div class="detail-content">{!! $job->yeu_cau_ung_vien !!}</div>
                    </div>
                    @endif

                    @if ($job->quyen_loi)
                    <div class="detail-section">
                        <h6>Quyền lợi được hưởng</h6>
                        <div class="detail-content">{!! $job->quyen_loi !!}</div>
                    </div>
                    @endif
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Đóng</button>
                    <a href="{{ route('employer.jobs.edit', $job->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i> Chỉnh sửa tin này
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xác nhận trước khi xóa tin
    document.querySelectorAll('.form-delete-job').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tin tuyển dụng này không?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection
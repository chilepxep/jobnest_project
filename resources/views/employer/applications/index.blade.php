@extends('layouts.contentNavbarLayout')

@section('title', 'Quản lý hồ sơ ứng viên')

@section('content')
@php
$statuses = \App\Models\Application::STATUSES;
$eduLabels = \App\Models\Candidate::EDUCATION_LABELS;
$totalAll = (int) $statusCounts->sum();
$currentStatus = request('trang_thai');
$hasFilter = request()->hasAny(['q', 'job_id', 'cv_access', 'trang_thai']);

// Biểu tượng (Boxicons) cho từng trạng thái
$statusIcons = [
'da_nop' => 'bx-envelope',
'dang_xem_xet' => 'bx-search-alt',
'phu_hop' => 'bx-star',
'moi_phong_van' => 'bx-calendar-check',
'da_tuyen' => 'bx-badge-check',
'tu_choi' => 'bx-x-circle',
];

// Màu avatar chữ cái đầu, cố định theo từng ứng viên (cùng 1 người luôn cùng 1 màu)
$avatarColors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

// Đếm theo trạng thái từ $statusCounts có sẵn (không tốn thêm truy vấn)
$count = fn ($code) => (int) ($statusCounts[$code] ?? 0);

// Dữ liệu cho 4 thẻ thống kê ở đầu trang. 'href' = null nghĩa là thẻ không bấm được.
$kpis = [
[
'label' => 'Tổng hồ sơ', 'value' => $totalAll, 'icon' => 'bx-folder-open', 'color' => 'primary',
'href' => route('employer.applications.index', request()->except(['trang_thai', 'page'])),
],
[
'label' => 'Chờ xem xét', 'value' => $count('da_nop'), 'icon' => 'bx-envelope', 'color' => 'warning',
'href' => route('employer.applications.index', array_merge(request()->except('page'), ['trang_thai' => 'da_nop'])),
],
[
'label' => 'Đang xử lý', 'icon' => 'bx-loader-circle', 'color' => 'info', 'href' => null,
'value' => $count('dang_xem_xet') + $count('phu_hop') + $count('moi_phong_van'),
],
[
'label' => 'Đã tuyển', 'value' => $count('da_tuyen'), 'icon' => 'bx-badge-check', 'color' => 'success',
'href' => route('employer.applications.index', array_merge(request()->except('page'), ['trang_thai' => 'da_tuyen'])),
],
];

// 2,0 -> "2 năm"; 1,5 -> "1,5 năm"
$fmtYears = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',') . ' năm';

// 15000000 -> "15 triệu"; 12500000 -> "12,5 triệu"; dưới 1 triệu -> "500.000 ₫"
$fmtSalary = function ($v) {
$v = (float) $v;

return $v >= 1000000
? rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',') . ' triệu'
: number_format($v, 0, ',', '.') . ' ₫';
};
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Tiêu đề trang --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Quản lý hồ sơ ứng viên</h4>
            <p class="text-muted mb-0">Theo dõi, sàng lọc và cập nhật tiến độ ứng tuyển của công ty.</p>
        </div>
        <a href="{{ route('employer.jobs.active') }}" class="btn btn-outline-primary">
            <i class="bx bx-briefcase me-1"></i> Danh sách tin tuyển dụng
        </a>
    </div>

    {{-- Thông báo flash --}}
    @foreach (['success' => ['success', 'bx-check-circle'], 'error' => ['danger', 'bx-error-circle'], 'info' => ['info',
    'bx-info-circle']] as $key => [$type, $alertIcon])
    @if (session($key))
    <div class="alert alert-{{ $type }} alert-dismissible d-flex align-items-center gap-2 fade show" role="alert">
        <i class="bx {{ $alertIcon }} fs-4"></i>
        <div>{{ session($key) }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
    @endif
    @endforeach

    {{-- Thẻ thống kê nhanh --}}
    <div class="row g-3 mb-4">
        @foreach ($kpis as $kpi)
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar avatar-md flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-{{ $kpi['color'] }}">
                            <i class="bx {{ $kpi['icon'] }} fs-4"></i>
                        </span>
                    </div>
                    <div>
                        <div class="text-muted small">{{ $kpi['label'] }}</div>
                        <h4 class="mb-0">{{ number_format($kpi['value']) }}</h4>
                    </div>
                </div>
                @if ($kpi['href'])
                <a href="{{ $kpi['href'] }}" class="stretched-link" aria-label="Lọc: {{ $kpi['label'] }}"></a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">

        {{-- Tab lọc nhanh theo trạng thái --}}
        <ul class="nav nav-tabs flex-nowrap overflow-auto px-3 pt-2" aria-label="Lọc theo trạng thái">
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ !$currentStatus ? 'active' : '' }}"
                    aria-current="{{ !$currentStatus ? 'page' : 'false' }}"
                    href="{{ route('employer.applications.index', request()->except(['trang_thai', 'page'])) }}">
                    Tất cả
                    <span
                        class="badge rounded-pill ms-1 {{ !$currentStatus ? 'bg-label-primary' : 'bg-label-secondary' }}">
                        {{ $totalAll }}
                    </span>
                </a>
            </li>

            @foreach ($statuses as $code => $meta)
            @php $isActiveTab = $currentStatus === $code; @endphp
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ $isActiveTab ? 'active' : '' }}"
                    aria-current="{{ $isActiveTab ? 'page' : 'false' }}"
                    href="{{ route('employer.applications.index', array_merge(request()->except('page'), ['trang_thai' => $code])) }}">
                    <i class="bx {{ $statusIcons[$code] ?? 'bx-circle' }} me-1"></i>{{ $meta['label'] }}
                    <span
                        class="badge rounded-pill ms-1 {{ $isActiveTab ? 'bg-label-primary' : 'bg-label-secondary' }}">
                        {{ $count($code) }}
                    </span>
                </a>
            </li>
            @endforeach
        </ul>

        {{-- Bộ lọc tìm kiếm --}}
        <form method="GET" action="{{ route('employer.applications.index') }}" class="card-body border-bottom py-3">
            @if ($currentStatus)
            <input type="hidden" name="trang_thai" value="{{ $currentStatus }}">
            @endif

            <div class="row g-2 align-items-center">
                <div class="col-lg-4">
                    <label for="q" class="visually-hidden">Tìm kiếm</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="search" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Tên ứng viên hoặc tiêu đề tin...">
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="job_id" class="visually-hidden">Tin tuyển dụng</label>
                    <select id="job_id" name="job_id" class="form-select">
                        <option value="">Tất cả tin tuyển dụng</option>
                        @foreach ($companyJobs as $job)
                        <option value="{{ $job->id }}" @selected((string) request('job_id')===(string) $job->id)>
                            {{ \Illuminate\Support\Str::limit($job->tieu_de, 50) }}
                            {{ $job->quyen_xem_cv_day_du ? '(Full CV)' : '(Khóa CV)' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="cv_access" class="visually-hidden">Quyền xem CV</label>
                    <select id="cv_access" name="cv_access" class="form-select">
                        <option value="">Mọi quyền xem CV</option>
                        <option value="1" @selected(request('cv_access')==='1' )>Hỗ trợ Full CV</option>
                        <option value="0" @selected(request('cv_access')==='0' )>Khóa liên hệ</option>
                    </select>
                </div>

                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bx bx-filter-alt me-1"></i> Lọc
                    </button>
                    @if ($hasFilter)
                    <a href="{{ route('employer.applications.index') }}" class="btn btn-outline-secondary"
                        title="Xóa bộ lọc" aria-label="Xóa bộ lọc">
                        <i class="bx bx-reset"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Bảng danh sách hồ sơ --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ứng viên</th>
                        <th>Vị trí ứng tuyển</th>
                        <th class="d-none d-lg-table-cell">Kinh nghiệm &amp; Lương</th>
                        <th>Trạng thái</th>
                        <th class="d-none d-md-table-cell">Ngày nộp</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $app)
                    @php
                    $candidate = $app->candidate;
                    $st = $statuses[$app->trang_thai] ?? ['label' => $app->trang_thai, 'badge' => 'bg-label-secondary'];
                    $stIcon = $statusIcons[$app->trang_thai] ?? 'bx-circle';
                    $name = $candidate?->ho_ten ?? 'Ứng viên';
                    $initial = mb_strtoupper(mb_substr($name, 0, 1));
                    $color = $avatarColors[($candidate?->id ?? 0) % count($avatarColors)];
                    $isNew = $app->trang_thai === 'da_nop';
                    $unlocked = $app->canViewFullContact();
                    $exp = $candidate?->so_nam_kinh_nghiem !== null ? $fmtYears($candidate->so_nam_kinh_nghiem) : 'Chưa
                    rõ';
                    $salary = $candidate?->muc_luong_mong_muon ? $fmtSalary($candidate->muc_luong_mong_muon) : 'Thỏa
                    thuận';
                    @endphp
                    <tr>
                        {{-- Ứng viên --}}
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($candidate?->user?->avatar_url)
                                <img src="{{ $candidate->user->avatar_url }}" alt="Ảnh đại diện {{ $name }}"
                                    class="rounded-circle object-fit-cover flex-shrink-0" width="42" height="42">
                                @else
                                <div class="avatar flex-shrink-0">
                                    <span
                                        class="avatar-initial rounded-circle bg-label-{{ $color }} fw-semibold">{{ $initial }}</span>
                                </div>
                                @endif

                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($isNew)
                                        <span class="d-inline-block rounded-circle bg-primary flex-shrink-0"
                                            style="width: 8px; height: 8px;" title="Hồ sơ mới, chưa xem"></span>
                                        @endif
                                        <a href="{{ route('employer.applications.show', $app) }}"
                                            class="{{ $isNew ? 'fw-bold' : 'fw-semibold' }} text-body">{{ $name }}</a>
                                    </div>
                                    <small class="text-muted d-block">
                                        <i
                                            class="bx bx-map me-1"></i>{{ $candidate?->province?->ten_tinh ?? 'Chưa cập nhật' }}
                                        @if ($candidate?->trinh_do_hoc_van)
                                        • {{ $eduLabels[$candidate->trinh_do_hoc_van] ?? $candidate->trinh_do_hoc_van }}
                                        @endif
                                    </small>
                                    {{-- Chỉ hiện trên màn hình nhỏ, nơi cột Kinh nghiệm & Lương bị ẩn --}}
                                    <small class="text-muted d-block d-lg-none">{{ $exp }} • {{ $salary }}</small>
                                </div>
                            </div>
                        </td>

                        {{-- Vị trí ứng tuyển + quyền xem CV --}}
                        <td>
                            <div class="fw-medium">
                                {{ \Illuminate\Support\Str::limit((string) $app->job?->tieu_de, 50) }}</div>
                            @if ($unlocked)
                            <span class="badge bg-label-success mt-1">
                                <i class="bx bx-check-shield me-1"></i>Hỗ trợ Full CV
                            </span>
                            @else
                            <span class="badge bg-label-warning mt-1">
                                <i class="bx bx-lock-alt me-1"></i>Khóa liên hệ
                            </span>
                            @endif
                        </td>

                        {{-- Kinh nghiệm & lương (ẩn trên màn hình nhỏ) --}}
                        <td class="d-none d-lg-table-cell">
                            <div class="small fw-medium"><i class="bx bx-time-five text-muted me-1"></i>{{ $exp }}</div>
                            <div class="small text-muted"><i class="bx bx-money me-1"></i>{{ $salary }}</div>
                        </td>

                        {{-- Trạng thái --}}
                        <td>
                            <span class="badge {{ $st['badge'] }}">
                                <i class="bx {{ $stIcon }} me-1"></i>{{ $st['label'] }}
                            </span>
                        </td>

                        {{-- Ngày nộp (ẩn trên màn hình rất nhỏ) --}}
                        <td class="d-none d-md-table-cell text-nowrap">
                            <div class="small fw-medium">{{ $app->ngay_ung_tuyen?->format('d/m/Y') }}</div>
                            <div class="small text-muted">{{ $app->ngay_ung_tuyen?->format('H:i') }}</div>
                        </td>

                        {{-- Thao tác --}}
                        <td class="text-end text-nowrap">
                            <a href="{{ route('employer.applications.show', $app) }}" class="btn btn-sm btn-primary"
                                title="Xem hồ sơ của {{ $name }}">
                                <i class="bx bx-show"></i><span class="d-none d-md-inline ms-1">Xem hồ sơ</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-5">
                            <div class="text-center">
                                <div class="avatar avatar-xl mx-auto mb-3">
                                    <span class="avatar-initial rounded-circle bg-label-secondary">
                                        <i class="bx bx-folder-open fs-1"></i>
                                    </span>
                                </div>
                                @if ($hasFilter)
                                <h6 class="mb-1">Không tìm thấy hồ sơ phù hợp</h6>
                                <p class="text-muted mb-3">Hãy thử đổi từ khóa hoặc bỏ bớt bộ lọc đang áp dụng.</p>
                                <a href="{{ route('employer.applications.index') }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-reset me-1"></i> Xóa bộ lọc
                                </a>
                                @else
                                <h6 class="mb-1">Chưa có hồ sơ ứng tuyển nào</h6>
                                <p class="text-muted mb-0">Khi ứng viên nộp hồ sơ vào các tin của bạn, chúng sẽ xuất
                                    hiện tại đây.</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Chân bảng: số lượng hiển thị + phân trang --}}
        @if ($applications->count())
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">
                Hiển thị {{ $applications->firstItem() }}–{{ $applications->lastItem() }} / {{ $applications->total() }}
                hồ sơ
            </small>
            @if ($applications->hasPages())
            {{ $applications->links() }}
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
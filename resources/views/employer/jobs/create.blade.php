@extends('layouts.contentNavbarLayout')

@section('title', 'Đăng tin tuyển dụng mới')

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
    rel="stylesheet" />
<style>
.page-hero {
    background: linear-gradient(135deg, #696cff 0%, #8f91ff 100%);
    border-radius: .5rem;
    color: #fff;
    padding: 1.5rem 1.75rem;
    position: relative;
    overflow: hidden;
}

.page-hero::after {
    content: "";
    position: absolute;
    right: -40px;
    top: -40px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .12);
}

.page-hero h4,
.page-hero p,
.page-hero .btn {
    color: #fff;
    position: relative;
    z-index: 1;
}

.page-hero .btn-light {
    color: #566a7f;
}

.section-head {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid rgba(67, 89, 113, .1);
}

.section-head h6 {
    margin: 0;
    font-weight: 600;
}

.section-head small {
    color: #a1acb8;
}

.form-label {
    font-weight: 500;
    margin-bottom: .35rem;
}

.form-hint {
    font-size: .78rem;
    color: #a1acb8;
    margin-top: .3rem;
}

.title-input {
    font-size: 1.1rem;
    padding: .75rem 1rem;
}

.work-type-group {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.work-type-group .btn {
    border-radius: 2rem;
    font-size: .8rem;
    padding: .35rem .85rem;
}

#salary_range_box {
    transition: opacity .2s ease;
}

.salary-preview {
    background: #f5f5ff;
    border: 1px dashed #c9caff;
    border-radius: .375rem;
    padding: .6rem .85rem;
    font-size: .85rem;
    color: #696cff;
    font-weight: 600;
    text-align: center;
}

.sticky-sidebar {
    position: sticky;
    top: 85px;
}

.location-item {
    background: #f8f9fa;
    border: 1px solid #e4e6e8;
    border-radius: .5rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1rem;
    position: relative;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.location-item.is-primary {
    background: #fdfdff;
    border-color: #696cff;
    box-shadow: 0 2px 8px rgba(105, 108, 255, .1);
}

.select2-container--bootstrap-5 .select2-selection {
    min-height: calc(1.5em + .94rem + 2px);
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
    background: #e7e7ff;
    color: #696cff;
    border: 0;
    border-radius: .25rem;
    font-size: .8rem;
}

.tox-tinymce {
    border-radius: .375rem !important;
    border: 1px solid #d9dee3 !important;
}
</style>
@endsection

@section('content')
<?php
$levels = [
    'thuc_tap_sinh' => 'Thực tập sinh',
    'nhan_vien' => 'Nhân viên',
    'truong_nhom' => 'Trưởng nhóm (Team Leader)',
    'quan_ly' => 'Quản lý (Supervisor/Manager)',
    'truong_phong' => 'Trưởng phòng',
    'giam_doc' => 'Giám đốc',
];
$workTypes = [
    'toan_thoi_gian' => ['Toàn thời gian', 'bx-time-five'],
    'ban_thoi_gian' => ['Bán thời gian', 'bx-time'],
    'thuc_tap' => ['Thực tập', 'bx-book-reader'],
    'tu_do' => ['Tự do', 'bx-laptop'],
    'tu_xa' => ['Làm từ xa', 'bx-home-alt'],
];
$industries = $industries ?? [];
$skills = $skills ?? [];
$provinces = $provinces ?? [];
$oldLocations = old('locations', [
    ['province_id' => '', 'district_id' => '', 'dia_chi_chi_tiet' => '']
]);
$oldPrimaryIndex = old('primary_location', 0);
$oldIndustries = (array) old('industry_ids', []);
$oldSkills = (array) old('skill_ids', []);
?>

<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Banner tiêu đề -->
    <div class="page-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <small class="text-white-50 text-uppercase fw-semibold">Quản lý việc làm</small>
            <h4 class="fw-bold mb-1 mt-1">Đăng tin tuyển dụng mới</h4>
            <p class="mb-0 opacity-75">Điền đầy đủ thông tin để tiếp cận ứng viên phù hợp nhất.</p>
        </div>
        <a href="{{ route('employer.dashboard') }}" class="btn btn-light">
            <i class="bx bx-arrow-back me-1"></i> Quay lại
        </a>
    </div>

    <!-- Thông báo lỗi -->
    <?php if (isset($errors) && $errors->any()): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <h6 class="alert-heading fw-bold mb-1"><i class="bx bx-error-circle me-1"></i> Vui lòng kiểm tra lại dữ liệu:
        </h6>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors->all() as $error): ?>
            <li>{{ $error }}</li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (session('error')): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form action="{{ route('employer.jobs.store') }}" method="POST" id="jobForm">
        @csrf

        <div class="row g-4">

            <!-- ========== CỘT TRÁI (8 PHẦN) ========== -->
            <div class="col-12 col-lg-8">

                <!-- Card 1: Thông tin chung -->
                <div class="card mb-4">
                    <div class="section-head">
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-primary"><i
                                    class="bx bx-briefcase-alt-2"></i></span>
                        </div>
                        <div>
                            <h6>1. Thông tin chung & Phân loại</h6>
                            <small>Tiêu đề, ngành nghề và kỹ năng cần có</small>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="mb-4">
                            <label for="tieu_de" class="form-label">Tiêu đề tin tuyển dụng <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control title-input" id="tieu_de" name="tieu_de"
                                value="{{ old('tieu_de') }}"
                                placeholder="VD: Lập trình viên Backend Laravel (Middle/Senior)" required>
                            <div class="form-hint">Tiêu đề rõ ràng, có vị trí và cấp bậc sẽ thu hút nhiều lượt xem hơn.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="industry_ids" class="form-label">Ngành nghề <span
                                        class="text-danger">*</span></label>
                                <select class="form-select select2-multiple" id="industry_ids" name="industry_ids[]"
                                    multiple="multiple" data-placeholder="Chọn tối đa 3 ngành nghề..." data-max="3"
                                    required>
                                    @error('industry_ids') ... @enderror
                                    <?php foreach ($industries as $industry): ?>
                                    <option value="{{ $industry->id }}"
                                        <?php echo in_array($industry->id, $oldIndustries) ? 'selected' : ''; ?>>
                                        {{ $industry->name ?? $industry->ten_nganh }}
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="skill_ids" class="form-label">Kỹ năng yêu cầu <span
                                        class="text-danger">*</span></label>
                                <select class="form-select select2-multiple" id="skill_ids" name="skill_ids[]"
                                    multiple="multiple" data-placeholder="Chọn các kỹ năng chuyên môn..." required>
                                    <?php foreach ($skills as $skill): ?>
                                    <option value="{{ $skill->id }}"
                                        <?php echo in_array($skill->id,$oldSkills) ? 'selected' : ''; ?>>
                                        {{ $skill->name ?? $skill->ten_ky_nang }}
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Nội dung chi tiết (TinyMCE) -->
                <div class="card mb-4">
                    <div class="section-head">
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-detail"></i></span>
                        </div>
                        <div>
                            <h6>2. Nội dung chi tiết công việc</h6>
                            <small>Mô tả, yêu cầu và quyền lợi dành cho ứng viên (Hỗ trợ định dạng văn bản)</small>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="mb-4">
                            <label for="mo_ta_cong_viec" class="form-label">Mô tả công việc <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control tinymce-editor" id="mo_ta_cong_viec" name="mo_ta_cong_viec"
                                rows="6">{{ old('mo_ta_cong_viec') }}</textarea>
                            <div class="text-danger small mt-1 d-none" id="desc_error">Vui lòng nhập mô tả công việc.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="yeu_cau_ung_vien" class="form-label">Yêu cầu ứng viên</label>
                            <textarea class="form-control tinymce-editor" id="yeu_cau_ung_vien" name="yeu_cau_ung_vien"
                                rows="6">{{ old('yeu_cau_ung_vien') }}</textarea>
                        </div>

                        <div>
                            <label for="quyen_loi" class="form-label">Quyền lợi được hưởng</label>
                            <textarea class="form-control tinymce-editor" id="quyen_loi" name="quyen_loi"
                                rows="5">{{ old('quyen_loi') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Khu vực làm việc (Nhiều địa điểm) -->
                <div class="card mb-4">
                    <div class="section-head justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md">
                                <span class="avatar-initial rounded bg-label-danger"><i
                                        class="bx bx-map-pin"></i></span>
                            </div>
                            <div>
                                <h6>3. Khu vực làm việc</h6>
                                <small>Có thể thêm nhiều chi nhánh / văn phòng làm việc cho tin tuyển dụng này</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAddLocation">
                            <i class="bx bx-plus me-1"></i> Thêm địa điểm
                        </button>
                    </div>
                    <div class="card-body pt-4">
                        <div id="locations_container">
                            <?php foreach ($oldLocations as $idx =>$loc): ?>
                            <div class="location-item <?php echo ($oldPrimaryIndex == $idx) ? 'is-primary' : ''; ?>"
                                data-index="{{ $idx }}">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input primary-radio" type="radio"
                                            name="primary_location" id="primary_loc_{{ $idx }}" value="{{ $idx }}"
                                            <?php echo ($oldPrimaryIndex ==$idx) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-semibold text-primary"
                                            for="primary_loc_{{ $idx }}">
                                            Địa điểm làm việc chính
                                        </label>
                                    </div>
                                    <button type="button"
                                        class="btn btn-xs btn-outline-danger btn-remove-location <?php echo (count($oldLocations) <= 1) ? 'd-none' : ''; ?>">
                                        <i class="bx bx-trash me-1"></i> Xóa
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Tỉnh / Thành phố <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select province-select"
                                            name="locations[{{ $idx }}][province_id]" required>
                                            <option value="">-- Chọn Tỉnh/Thành --</option>
                                            <?php foreach ($provinces as $province): ?>
                                            <option value="{{ $province->id }}"
                                                <?php echo (($loc['province_id'] ?? '') ==$province->id) ? 'selected' : ''; ?>>
                                                {{ $province->ten_tinh }}
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        @error("locations.$idx.province_id")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Quận / Huyện</label>
                                        <select class="form-select district-select"
                                            name="locations[{{ $idx }}][district_id]"
                                            data-selected="{{ $loc['district_id'] ?? '' }}">
                                            <option value="">-- Chọn Quận/Huyện --</option>

                                        </select>
                                        @error("locations.$idx.district_id")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Địa chỉ cụ thể (Số nhà, đường...)</label>
                                        <input type="text" class="form-control"
                                            name="locations[{{ $idx }}][dia_chi_chi_tiet]"
                                            value="{{ $loc['dia_chi_chi_tiet'] ?? '' }}"
                                            placeholder="VD: Tầng 5, Tòa nhà ABC...">
                                        @error("locations.$idx.dia_chi_chi_tiet")
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        @error('locations')
                        <div class="alert alert-danger py-2 px-3 small mt-2 mb-0">
                            <i class="bx bx-error-circle me-1"></i> {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>

            </div>

            <!-- ========== CỘT PHẢI (4 PHẦN) ========== -->
            <div class="col-12 col-lg-4">
                <div class="sticky-sidebar">

                    <!-- Card 4: Lương -->
                    <div class="card mb-4">
                        <div class="section-head justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-md">
                                    <span class="avatar-initial rounded bg-label-success"><i
                                            class="bx bx-dollar-circle"></i></span>
                                </div>
                                <div>
                                    <h6>4. Mức đãi ngộ</h6>
                                    <small>Đơn vị: VNĐ / tháng</small>
                                </div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="luong_thoa_thuan"
                                    name="luong_thoa_thuan" value="1"
                                    <?php echo old('luong_thoa_thuan') ? 'checked' : ''; ?>>
                                <label class="form-check-label small fw-semibold text-primary"
                                    for="luong_thoa_thuan">Thỏa thuận</label>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div id="salary_range_box">
                                <div class="mb-3">
                                    <label for="luong_toi_thieu" class="form-label">Lương tối thiểu</label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control @error('luong_toi_thieu') is-invalid @enderror"
                                            id="luong_toi_thieu" name="luong_toi_thieu"
                                            value="{{ old('luong_toi_thieu') }}" placeholder="VD: 10000000" min="0"
                                            step="1">
                                        <span class="input-group-text">₫</span>
                                    </div>
                                    @error('luong_toi_thieu')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="luong_toi_da" class="form-label">Lương tối đa</label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control @error('luong_toi_da') is-invalid @enderror"
                                            id="luong_toi_da" name="luong_toi_da" value="{{ old('luong_toi_da') }}"
                                            placeholder="VD: 25000000" min="0" step="1">
                                        <span class="input-group-text">₫</span>
                                    </div>
                                    <div class="text-danger small mt-1 d-none" id="salary_error">
                                        Lương tối đa phải lớn hơn hoặc bằng lương tối thiểu.
                                    </div>
                                    @error('luong_toi_da')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="salary-preview" id="salary_preview">Chưa nhập mức lương</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: Yêu cầu chung -->
                    <div class="card mb-4">
                        <div class="section-head">
                            <div class="avatar avatar-md">
                                <span class="avatar-initial rounded bg-label-warning"><i
                                        class="bx bx-slider-alt"></i></span>
                            </div>
                            <div>
                                <h6>5. Yêu cầu chung</h6>
                                <small>Hình thức, cấp bậc và thời hạn</small>
                            </div>
                        </div>
                        <div class="card-body pt-4">
                            <div class="mb-4">
                                <label class="form-label">Hình thức làm việc <span class="text-danger">*</span></label>
                                <div class="work-type-group">
                                    <?php foreach ($workTypes as $value =>$item): ?>
                                    <input type="radio" class="btn-check" name="loai_hinh_lam_viec" id="lh_{{ $value }}"
                                        value="{{ $value }}"
                                        <?php echo (old('loai_hinh_lam_viec', 'toan_thoi_gian') === $value) ? 'checked' : ''; ?>
                                        required>
                                    <label class="btn btn-outline-primary" for="lh_{{ $value }}">
                                        <i class="bx {{ $item[1] }} me-1"></i>{{ $item[0] }}
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="cap_bac" class="form-label">Cấp bậc <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="cap_bac" name="cap_bac" required>
                                    <?php foreach ($levels as $value =>$label): ?>
                                    <option value="{{ $value }}"
                                        <?php echo (old('cap_bac', 'nhan_vien') === $value) ? 'selected' : ''; ?>>
                                        {{ $label }}
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="kinh_nghiem_toi_thieu" class="form-label">Kinh nghiệm</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="kinh_nghiem_toi_thieu"
                                            name="kinh_nghiem_toi_thieu" value="{{ old('kinh_nghiem_toi_thieu', 0) }}"
                                            min="0" max="50">
                                        <span class="input-group-text">năm</span>
                                    </div>
                                    <div class="form-hint">0 = không yêu cầu</div>
                                </div>

                                <div class="col-6 mb-3">
                                    <label for="so_luong_tuyen" class="form-label">Số lượng <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="so_luong_tuyen"
                                            name="so_luong_tuyen" value="{{ old('so_luong_tuyen', 1) }}" min="1"
                                            required>
                                        <span class="input-group-text">người</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="han_nop_ho_so" class="form-label">Hạn chót nộp hồ sơ <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="han_nop_ho_so" name="han_nop_ho_so"
                                    value="{{ old('han_nop_ho_so') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                    required>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-xs btn-label-secondary quick-date"
                                        data-days="15">+15 ngày</button>
                                    <button type="button" class="btn btn-xs btn-label-secondary quick-date"
                                        data-days="30">+30 ngày</button>
                                    <button type="button" class="btn btn-xs btn-label-secondary quick-date"
                                        data-days="60">+60 ngày</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ========== THANH NÚT LƯU (CĂN GIỮA 2 CỘT Ở CUỐI FORM) ========== -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 px-4">
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bx bx-info-circle text-primary fs-5 me-2"></i>
                            <span>Kiểm tra kỹ các thông tin bắt buộc (<span class="text-danger">*</span>) trước khi gửi
                                xét duyệt hoặc lưu nháp.</span>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('employer.jobs.active') }}" class="btn btn-outline-secondary">
                                Hủy
                            </a>
                            <button type="submit" name="action_status" value="nhap" class="btn btn-label-secondary">
                                <i class="bx bx-save me-1"></i> Lưu bản nháp
                            </button>
                            <button type="submit" name="action_status" value="submit" class="btn btn-primary">
                                <i class="bx bx-send me-1"></i> Gửi duyệt ngay
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Template ẩn dùng để nhân bản khi bấm "Thêm địa điểm" -->
<template id="location_template">
    <div class="location-item" data-index="__INDEX__">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check mb-0">
                <input class="form-check-input primary-radio" type="radio" name="primary_location"
                    id="primary_loc___INDEX__" value="__INDEX__">
                <label class="form-check-label fw-semibold text-primary" for="primary_loc___INDEX__">
                    Địa điểm làm việc chính
                </label>
            </div>
            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-location">
                <i class="bx bx-trash me-1"></i> Xóa
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                <select class="form-select province-select" name="locations[__INDEX__][province_id]" required>
                    <option value="">-- Chọn Tỉnh/Thành --</option>
                    <?php foreach ($provinces as $province): ?>
                    <option value="{{ $province->id }}">{{ $province->name ?? $province->ten_tinh }}</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Quận / Huyện</label>
                <select class="form-select district-select" name="locations[__INDEX__][district_id]">
                    <option value="">-- Chọn Quận/Huyện --</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Địa chỉ cụ thể (Số nhà, đường...)</label>
                <input type="text" class="form-control" name="locations[__INDEX__][dia_chi_chi_tiet]"
                    placeholder="VD: Tầng 5, Tòa nhà ABC...">
            </div>
        </div>
    </div>
</template>
@endsection

@section('page-script')
<script>
(function() {
    function loadScript(src) {
        return new Promise(function(resolve, reject) {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    async function boot() {
        if (typeof window.jQuery === 'undefined') {
            await loadScript('https://code.jquery.com/jquery-3.7.1.min.js');
        }
        if (!window.jQuery.fn.select2) {
            await loadScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');
        }
        if (typeof window.tinymce === 'undefined') {
            await loadScript('https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js');
        }
        init(window.jQuery);
    }

    function init($) {
        // 1. Select2
        $('.select2-multiple').each(function() {
            const max = $(this).data('max') || 0;
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                closeOnSelect: false,
                placeholder: $(this).data('placeholder'),
                maximumSelectionLength: max,
                language: {
                    maximumSelected: function(e) {
                        return 'Chỉ được chọn tối đa ' + e.maximum + ' mục';
                    },
                    noResults: function() {
                        return 'Không tìm thấy kết quả';
                    }
                }
            });
        });

        // 2. TinyMCE
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '.tinymce-editor',
                height: 240,
                menubar: false,
                statusbar: false,
                plugins: 'lists link autolink',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link removeformat',
                content_style: 'body { font-family: Public Sans, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; font-size: 14px; color: #566a7f; line-height: 1.6; }',
                setup: function(editor) {
                    editor.on('change keyup', function() {
                        editor.save();
                        if (editor.id === 'mo_ta_cong_viec') {
                            document.getElementById('desc_error').classList.add('d-none');
                        }
                    });
                }
            });
        }

        // 3. Mức Lương
        const chk = document.getElementById('luong_thoa_thuan');
        const box = document.getElementById('salary_range_box');
        const inMin = document.getElementById('luong_toi_thieu');
        const inMax = document.getElementById('luong_toi_da');
        const preview = document.getElementById('salary_preview');
        const errBox = document.getElementById('salary_error');

        const fmt = v => Number(v).toLocaleString('vi-VN') + ' ₫';

        function updateSalary() {
            const min = inMin.value ? Number(inMin.value) : null;
            const max = inMax.value ? Number(inMax.value) : null;

            if (min !== null && max !== null) {
                preview.textContent = fmt(min) + '  –  ' + fmt(max);
            } else if (min !== null) {
                preview.textContent = 'Từ ' + fmt(min);
            } else if (max !== null) {
                preview.textContent = 'Lên đến ' + fmt(max);
            } else {
                preview.textContent = 'Chưa nhập mức lương';
            }

            const invalid = min !== null && max !== null && max < min;
            errBox.classList.toggle('d-none', !invalid);
            inMax.classList.toggle('is-invalid', invalid);
        }

        function toggleSalary() {
            const off = chk.checked;
            box.style.opacity = off ? '0.4' : '1';
            box.style.pointerEvents = off ? 'none' : 'auto';
            inMin.disabled = off;
            inMax.disabled = off;
            if (off) {
                inMin.value = '';
                inMax.value = '';
                errBox.classList.add('d-none');
                inMax.classList.remove('is-invalid');
                preview.textContent = 'Lương thỏa thuận';
            } else {
                updateSalary();
            }
        }

        chk.addEventListener('change', toggleSalary);
        inMin.addEventListener('input', updateSalary);
        inMax.addEventListener('input', updateSalary);
        toggleSalary();

        // 4. Chọn nhanh hạn nộp hồ sơ
        const dateInput = document.getElementById('han_nop_ho_so');
        document.querySelectorAll('.quick-date').forEach(btn => {
            btn.addEventListener('click', function() {
                const d = new Date();
                d.setDate(d.getDate() + Number(this.dataset.days));
                const pad = n => String(n).padStart(2, '0');
                dateInput.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d
                    .getDate());
            });
        });

        // 5. Nhiều Địa Điểm
        const locationsContainer = document.getElementById('locations_container');
        const btnAddLocation = document.getElementById('btnAddLocation');
        const templateHtml = document.getElementById('location_template').innerHTML;
        let locationIndex = locationsContainer.querySelectorAll('.location-item').length;

        function fetchDistrictsForRow(provinceSelect, selectedDistrictId = null) {
            const row = provinceSelect.closest('.location-item');
            const districtSelect = row.querySelector('.district-select');
            const provinceId = provinceSelect.value;

            districtSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
            if (!provinceId) return;

            districtSelect.disabled = true;
            fetch(`/api/districts/${provinceId}`)
                .then(r => r.json())
                .then(data => {
                    data.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name || d.ten_quan_huyen;
                        if (selectedDistrictId && String(selectedDistrictId) === String(d.id)) {
                            opt.selected = true;
                        }
                        districtSelect.appendChild(opt);
                    });
                })
                .catch(err => console.error('Lỗi khi tải Quận/Huyện:', err))
                .finally(() => {
                    districtSelect.disabled = false;
                });
        }

        function updateRemoveButtons() {
            const items = locationsContainer.querySelectorAll('.location-item');
            items.forEach(item => {
                const btnRemove = item.querySelector('.btn-remove-location');
                btnRemove.classList.toggle('d-none', items.length <= 1);
            });
        }

        locationsContainer.querySelectorAll('.location-item').forEach(item => {
            const provSelect = item.querySelector('.province-select');
            const distSelect = item.querySelector('.district-select');
            const oldDist = distSelect.getAttribute('data-selected');
            if (provSelect.value) {
                fetchDistrictsForRow(provSelect, oldDist);
            }
        });

        locationsContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('province-select')) {
                fetchDistrictsForRow(e.target);
            }
            if (e.target.classList.contains('primary-radio')) {
                locationsContainer.querySelectorAll('.location-item').forEach(el => el.classList.remove(
                    'is-primary'));
                e.target.closest('.location-item').classList.add('is-primary');
            }
        });

        locationsContainer.addEventListener('click', function(e) {
            const btnRemove = e.target.closest('.btn-remove-location');
            if (btnRemove) {
                const itemToRemove = btnRemove.closest('.location-item');
                const wasPrimary = itemToRemove.querySelector('.primary-radio').checked;
                itemToRemove.remove();

                if (wasPrimary) {
                    const firstItem = locationsContainer.querySelector('.location-item');
                    if (firstItem) {
                        firstItem.classList.add('is-primary');
                        firstItem.querySelector('.primary-radio').checked = true;
                    }
                }
                updateRemoveButtons();
            }
        });

        btnAddLocation.addEventListener('click', function() {
            const newHtml = templateHtml.replace(/__INDEX__/g, locationIndex++);
            locationsContainer.insertAdjacentHTML('beforeend', newHtml);
            updateRemoveButtons();
        });

        // 6. Validate trước khi Submit
        document.getElementById('jobForm').addEventListener('submit', function(e) {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            const descContent = document.getElementById('mo_ta_cong_viec').value.trim();
            if (!descContent) {
                e.preventDefault();
                const descErr = document.getElementById('desc_error');
                descErr.classList.remove('d-none');
                descErr.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                return;
            }

            if (!errBox.classList.contains('d-none')) {
                e.preventDefault();
                inMax.focus();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', boot);
})();
</script>
@endsection
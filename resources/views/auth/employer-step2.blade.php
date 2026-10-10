@extends('layouts.blankLayout')

@section('title', 'Bước 2: Thông tin Công ty')

@section('page-style')
<style>
/* Ảnh bên phải */
.auth-image-side {
    background-image: url("{{ asset('images/background_auth.png') }}");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-radius: 60px 0 0 60px;
    min-height: 100vh;
}

/* Form bên trái */
.auth-form-side {
    min-height: 100vh;
    background: #fff;
}

.auth-form-inner {
    width: 100%;
    /* MỞ RỘNG TỪ 440px LÊN 750px ĐỂ CHỨA 2 CỘT */
    max-width: 750px;
}

/* Stepper */
.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.stepper-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.stepper-item::before {
    position: absolute;
    content: "";
    border-bottom: 2px solid #ccc;
    width: 100%;
    top: 15px;
    left: -50%;
    z-index: 2;
}

.stepper-item:first-child::before {
    content: none;
}

.step-counter {
    position: relative;
    z-index: 5;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ccc;
    color: #fff;
    font-weight: bold;
}

.stepper-item.active .step-counter,
.stepper-item.completed .step-counter {
    background: #f5a000;
}

.stepper-item.completed::before {
    border-color: #f5a000;
}

.step-name {
    margin-top: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #a1acb8;
    text-align: center;
}

.stepper-item.active .step-name {
    color: #f5a000;
}
</style>
@endsection

@section('content')
<div class="row g-0 w-100 m-0 bg-white">

    <!-- Bên trái: Form -->
    <div class="col-12 col-lg-7 auth-form-side d-flex align-items-center justify-content-center p-4 p-md-5">
        <div class="auth-form-inner">

            <!-- Stepper (Cập nhật active cho Bước 2) -->
            <div class="stepper-wrapper">
                <div class="stepper-item completed">
                    <div class="step-counter"><i class="bx bx-check"></i></div>
                    <div class="step-name">Tài khoản</div>
                </div>
                <div class="stepper-item active">
                    <div class="step-counter">2</div>
                    <div class="step-name">Thông tin Công ty</div>
                </div>
                <div class="stepper-item">
                    <div class="step-counter">3</div>
                    <div class="step-name">Chờ xác duyệt</div>
                </div>
            </div>

            <h4 class="mb-1 text-center text-primary fw-bold">Bước 2: Thông tin Doanh nghiệp</h4>
            <p class="mb-4 text-center text-muted">Vui lòng cung cấp thông tin chính xác để quá trình duyệt diễn ra
                nhanh chóng.</p>

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('employer.register.step2') }}" method="POST">
                @csrf

                <div class="row g-4">
                    <!-- CỘT 1: Người liên hệ & Khác -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark mb-3">1. Người đại diện tuyển dụng (HR)</h6>

                        <div class="mb-3">
                            <label for="ho_ten" class="form-label">Họ tên người liên hệ (*)</label>
                            <input type="text" class="form-control" id="ho_ten" name="ho_ten"
                                value="{{ old('ho_ten') }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="chuc_vu" class="form-label">Chức vụ (*)</label>
                            <input type="text" class="form-control" id="chuc_vu" name="chuc_vu"
                                value="{{ old('chuc_vu') }}" required>
                        </div>

                        <h6 class="fw-bold text-dark mb-3">2. Thông tin liên hệ công ty</h6>

                        <div class="mb-3">
                            <label for="email_lien_he" class="form-label">Email công ty (*)</label>
                            <input type="email" class="form-control" id="email_lien_he" name="email_lien_he"
                                value="{{ old('email_lien_he') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="hotline" class="form-label">Hotline công ty (*)</label>
                            <input type="text" class="form-control" id="hotline" name="hotline"
                                value="{{ old('hotline') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Website (Tùy chọn)</label>
                            <input type="url" class="form-control" id="website" name="website"
                                value="{{ old('website') }}" placeholder="https://...">
                        </div>
                    </div>

                    <!-- CỘT 2: Thông tin Doanh nghiệp & Vị trí -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-dark mb-3">3. Hồ sơ Doanh nghiệp</h6>

                        <div class="mb-3">
                            <label for="ten_cong_ty" class="form-label">Tên Doanh nghiệp (*)</label>
                            <input type="text" class="form-control" id="ten_cong_ty" name="ten_cong_ty"
                                value="{{ old('ten_cong_ty') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="ma_so_thue" class="form-label">Mã số thuế (*)</label>
                            <input type="text" class="form-control" id="ma_so_thue" name="ma_so_thue"
                                value="{{ old('ma_so_thue') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="quy_mo" class="form-label">Quy mô (*)</label>
                                <select class="form-select" id="quy_mo" name="quy_mo" required>
                                    <option value="">-- Chọn --</option>
                                    <option value="duoi_25" {{ old('quy_mo') == 'duoi_25' ? 'selected' : '' }}>Dưới 25
                                        NV</option>
                                    <option value="25_99" {{ old('quy_mo') == '25_99' ? 'selected' : '' }}>25 - 99 NV
                                    </option>
                                    <option value="100_499" {{ old('quy_mo') == '100_499' ? 'selected' : '' }}>100 - 499
                                        NV</option>
                                    <option value="500_999" {{ old('quy_mo') == '500_999' ? 'selected' : '' }}>500 - 999
                                        NV</option>
                                    <option value="tren_1000" {{ old('quy_mo') == 'tren_1000' ? 'selected' : '' }}>Trên
                                        1000 NV</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="industry_id" class="form-label">Lĩnh vực (*)</label>
                                <select class="form-select" id="industry_id" name="industry_id" required>
                                    <option value="">-- Chọn --</option>
                                    @foreach($industries as $industry)
                                    <option value="{{ $industry->id }}"
                                        {{ old('industry_id') == $industry->id ? 'selected' : '' }}>
                                        {{ $industry->ten_nganh }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="province_id" class="form-label">Tỉnh/Thành phố (*)</label>
                                <select class="form-select" id="province_id" name="province_id" required>
                                    <option value="">-- Chọn Tỉnh --</option>
                                    @foreach($provinces as $province)
                                    <option value="{{ $province->id }}"
                                        {{ old('province_id') == $province->id ? 'selected' : '' }}>
                                        {{ $province->ten_tinh }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="district_id" class="form-label">Quận/Huyện (*)</label>
                                <select class="form-select" id="district_id" name="district_id" required>
                                    <option value="">-- Chọn Quận --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- FULL WIDTH: Địa chỉ chi tiết và Mô tả -->
                    <div class="col-12 mt-2">
                        <div class="mb-3">
                            <label for="dia_chi_chi_tiet" class="form-label">Địa chỉ chi tiết (Số nhà, Tòa nhà,
                                Đường...) (*)</label>
                            <input type="text" class="form-control" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet"
                                value="{{ old('dia_chi_chi_tiet') }}" required>
                        </div>

                        <div class="mb-4">
                            <label for="mo_ta" class="form-label">Giới thiệu ngắn gọn về doanh nghiệp (*)</label>
                            <textarea class="form-control" id="mo_ta" name="mo_ta" rows="3"
                                required>{{ old('mo_ta') }}</textarea>
                        </div>

                        <button class="btn btn-success w-100 py-2" type="submit">Gửi hồ sơ xét duyệt</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Bên phải: Ảnh -->
    <div class="col-lg-5 d-none d-lg-block bg-white p-0">
        <div class="auth-image-side w-100 h-100"></div>
    </div>

</div>
@endsection

@section('page-script')
<script>
document.addEventListener("DOMContentLoaded", function() {
    const provinceSelect = document.getElementById('province_id');
    const districtSelect = document.getElementById('district_id');

    // Giữ lại Quận/Huyện cũ nếu validation lỗi
    const oldDistrictId = "{{ old('district_id') }}";

    function fetchDistricts(provinceId, selectedDistrictId = null) {
        districtSelect.innerHTML = '<option value="">-- Chọn Quận --</option>';
        if (!provinceId) return;

        // Gọi API lấy dữ liệu AJAX
        fetch(`/api/districts/${provinceId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district
                        .ten_quan_huyen;

                    if (selectedDistrictId && selectedDistrictId == district.id) {
                        option.selected = true;
                    }
                    districtSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Lỗi khi tải Quận/Huyện:', error));
    }

    provinceSelect.addEventListener('change', function() {
        fetchDistricts(this.value);
    });

    // Kích hoạt ngay khi trang load xong nếu đã có Tỉnh được chọn (trường hợp load lại form do lỗi)
    if (provinceSelect.value) {
        fetchDistricts(provinceSelect.value, oldDistrictId);
    }
});
</script>
@endsection
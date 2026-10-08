<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/employer/dashboard', function () {
    // 1. Tạo dữ liệu giả cho tài khoản (Sử dụng stdClass để giả lập Object lấy từ CSDL)
    $employer = new \stdClass();
    $employer->ho_ten = 'Nguyễn Văn Test';
    $employer->chuc_vu = 'Trưởng phòng HR';
    
    $employer->company = new \stdClass();
    $employer->company->ten_cong_ty = 'Công ty Cổ phần Công nghệ Jobnest';

    // 4. Tạo dữ liệu giả cho Biểu đồ
    //tổng số ứng viên ứng tuyển theo tháng
    $chartData = [6, 7, 9, 20, 10, 5];
    //tổng tin ứng tuyển theo tháng
    $chartDataTinTuyen = [3, 5, 7, 3, 1, 9];
    //6 tháng gần nhất
    $chartCategories = ['Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10'];

    //số ứng viên lần lượt đã được chọn đi và từ chối
    $chartDataCircle = [50,96];
    //tổng số ứng viên
    $charDataTotelUngVien = 100;
    $chartDataCircleLabel = ['Đã được chọn', 'Từ chối'];

    // số tin tuyển dụng lần lượt là tin còn hoạt động, đã tạm dừng, đã hết hạn
    $chartDataCircleTin = [12,5,9];
    //tổng số bài tin đã đăng
    $charDataTotelTin = 100;
    $chartDataCircleLabelTin = ['Hoạt động','Tạm dừng', 'Hết Hạn'];

    // 5. Trả về view kèm toàn bộ dữ liệu giả
    return view('employer.dashboard', compact(
        'employer', 
        'chartData', 
        'chartDataTinTuyen',
        'chartCategories',
        'charDataTotelUngVien',
        'chartDataCircle',
        'chartDataCircleLabel',
        'chartDataCircleTin',
        'charDataTotelTin',
        'chartDataCircleLabelTin'
    ));
})->name('employer.dashboard');;

// Route::get('/employer/dashboard', function () {
//     return view('employer.dashboard');
// })->name('employer.dashboard');
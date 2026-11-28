<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployerApplicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Đổi số này thành company_id của tài khoản HR bạn đang đăng nhập (để null sẽ lấy employer đầu tiên)
        $targetCompanyId = 11;

        $employer = $targetCompanyId
            ? DB::table('employers')->where('company_id', $targetCompanyId)->first()
            : DB::table('employers')->orderBy('id')->first();

        if (!$employer) {
            $this->command->error('Chưa có dữ liệu trong bảng employers. Hãy tạo tài khoản NTD trước!');
            return;
        }

        // Đảm bảo công ty đang ở trạng thái da_duyet để qua JobPolicy
        DB::table('companies')
            ->where('id', $employer->company_id)
            ->update(['trang_thai' => 'da_duyet']);

        // Lấy sẵn ID thực từ DB để tránh lỗi khóa ngoại
        $provinceId = DB::table('provinces')->value('id');
        $districtId = DB::table('districts')->where('province_id', $provinceId)->value('id');
        $industryId = DB::table('industries')->value('id');
        $skillIds   = DB::table('skills')->limit(6)->pluck('id')->all();

        // Tạo 1 file PDF mẫu tối giản để test nút Xem/Tải CV không bị 404
        $demoCvPath = 'cvs/demo-candidate-cv.pdf';
        if (!Storage::disk('local')->exists($demoCvPath)) {
            $minimalPdf = "%PDF-1.4\n1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>endobj\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \ntrailer<< /Size 4 /Root 1 0 R >>\nstartxref\n190\n%%EOF";
            Storage::disk('local')->put($demoCvPath, $minimalPdf);
        }

        DB::transaction(function () use ($employer, $provinceId, $districtId, $industryId, $skillIds, $demoCvPath) {
            $now = now();

            // 1. Đảm bảo có gói Free và 1 gói Pro mẫu
            DB::table('packages')->updateOrInsert(
                ['ma_goi' => 'free'],
                [
                    'ten_goi'              => 'Gói Trải Nghiệm (Miễn Phí)',
                    'loai'                 => 'goi_dich_vu',
                    'gia'                  => 0,
                    'so_ngay_hieu_luc'     => null,
                    'so_luot'              => 0,
                    'so_tin_xem_cv_day_du' => 3,
                    'so_tin_uu_tien'       => 0,
                    'so_tin_hot'           => 0,
                    'hien_thi_noi_bat'     => 0,
                    'mo_ta'                => 'Tặng 3 tin đầu tiên được xem đầy đủ thông tin liên hệ và CV ứng viên.',
                    'dang_ban'             => 1,
                ]
            );

            DB::table('packages')->updateOrInsert(
                ['ma_goi' => 'pro_30'],
                [
                    'ten_goi'              => 'Gói Chuyên Nghiệp (30 ngày)',
                    'loai'                 => 'goi_dich_vu',
                    'gia'                  => 1500000,
                    'so_ngay_hieu_luc'     => 30,
                    'so_luot'              => 10,
                    'so_tin_xem_cv_day_du' => 10,
                    'so_tin_uu_tien'       => 3,
                    'so_tin_hot'           => 2,
                    'hien_thi_noi_bat'     => 1,
                    'mo_ta'                => 'Mở khóa xem Full CV cho 10 tin tuyển dụng kèm đặc quyền Tin Hot & Tuyển Gấp.',
                    'dang_ban'             => 1,
                ]
            );

            $freePackageId = DB::table('packages')->where('ma_goi', 'free')->value('id');

            // 2. Tạo đơn gói Free cho công ty (đã dùng hết 3/3 lượt)
            $freeOrder = DB::table('company_orders')
                ->where('company_id', $employer->company_id)
                ->where('package_id', $freePackageId)
                ->first();

            if (!$freeOrder) {
                $freeOrderId = DB::table('company_orders')->insertGetId([
                    'company_id'            => $employer->company_id,
                    'package_id'            => $freePackageId,
                    'so_tien'               => 0,
                    'phuong_thuc_thanh_toan'=> null,
                    'ma_giao_dich'          => 'FREE-DEMO-' . $employer->company_id,
                    'trang_thai_thanh_toan' => 'da_thanh_toan',
                    'so_luot_con_lai'       => 0,
                    'gioi_han_snapshot'     => json_encode([
                        'so_tin_xem_cv_day_du' => 3,
                        'so_tin_uu_tien'       => 0,
                        'so_tin_hot'           => 0,
                    ]),
                    'da_dung_tin_xem_cv'    => 3,
                    'da_dung_tin_uu_tien'   => 0,
                    'da_dung_tin_hot'       => 0,
                    'ngay_mua'              => $now->copy()->subDays(10),
                    'ngay_het_han'          => null,
                ]);
            } else {
                $freeOrderId = $freeOrder->id;
            }

            // 3. Tạo 4 tin tuyển dụng: 3 tin Full CV + 1 tin thứ 4 bị khóa CV
            $jobDefs = [
                [
                    'slug' => 'demo-php-laravel-dev-' . $employer->company_id,
                    'tieu_de' => 'Lập trình viên PHP / Laravel (Full CV - Tin #1)',
                    'cap_bac' => 'nhan_vien',
                    'luong_min' => 15000000,
                    'luong_max' => 25000000,
                    'full_cv' => 1,
                    'order_id' => $freeOrderId,
                ],
                [
                    'slug' => 'demo-frontend-vue-react-' . $employer->company_id,
                    'tieu_de' => 'Lập trình viên Frontend React/Vue (Full CV - Tin #2)',
                    'cap_bac' => 'nhan_vien',
                    'luong_min' => 14000000,
                    'luong_max' => 22000000,
                    'full_cv' => 1,
                    'order_id' => $freeOrderId,
                ],
                [
                    'slug' => 'demo-qa-tester-manual-' . $employer->company_id,
                    'tieu_de' => 'Chuyên viên Kiểm thử phần mềm QA/QC (Full CV - Tin #3)',
                    'cap_bac' => 'nhan_vien',
                    'luong_min' => 12000000,
                    'luong_max' => 18000000,
                    'full_cv' => 1,
                    'order_id' => $freeOrderId,
                ],
                [
                    'slug' => 'demo-business-analyst-locked-' . $employer->company_id,
                    'tieu_de' => 'Chuyên viên Phân tích nghiệp vụ BA (Khóa CV - Tin #4)',
                    'cap_bac' => 'truong_nhom',
                    'luong_min' => 18000000,
                    'luong_max' => 30000000,
                    'full_cv' => 0, // Hết hạn mức Free -> che thông tin ứng viên
                    'order_id' => null,
                ],
            ];

            $jobIds = [];
            foreach ($jobDefs as $j) {
                $existingJob = DB::table('jobs')->where('slug', $j['slug'])->first();
                if ($existingJob) {
                    $jobId = $existingJob->id;
                } else {
                    $jobId = DB::table('jobs')->insertGetId([
                        'company_id'            => $employer->company_id,
                        'employer_id'           => $employer->id,
                        'tieu_de'               => $j['tieu_de'],
                        'slug'                  => $j['slug'],
                        'mo_ta_cong_viec'       => 'Phát triển và bảo trì hệ thống web, phối hợp với đội ngũ sản phẩm để tối ưu trải nghiệm người dùng.',
                        'yeu_cau_ung_vien'      => 'Có từ 1-3 năm kinh nghiệm thực tế, tư duy giải quyết vấn đề tốt.',
                        'quyen_loi'             => 'Thưởng tháng 13, bảo hiểm đầy đủ, review lương 2 lần/năm.',
                        'loai_hinh_lam_viec'    => 'toan_thoi_gian',
                        'cap_bac'               => $j['cap_bac'],
                        'kinh_nghiem_toi_thieu' => 2,
                        'luong_toi_thieu'       => $j['luong_min'],
                        'luong_toi_da'          => $j['luong_max'],
                        'luong_thoa_thuan'      => 0,
                        'so_luong_tuyen'        => 2,
                        'han_nop_ho_so'         => $now->copy()->addDays(30)->toDateString(),
                        'trang_thai'            => 'dang_tuyen',
                        'quyen_xem_cv_day_du'   => $j['full_cv'],
                        'company_order_id'      => $j['order_id'],
                        'luot_xem'              => rand(40, 180),
                        'luot_ung_tuyen'        => 0,
                        'ngay_dang'             => $now->copy()->subDays(5),
                        'created_at'            => $now->copy()->subDays(6),
                        'updated_at'            => $now,
                    ]);

                    if ($provinceId) {
                        DB::table('job_locations')->insert([
                            'job_id'            => $jobId,
                            'province_id'       => $provinceId,
                            'district_id'       => $districtId,
                            'dia_chi_chi_tiet'  => 'Tầng 8, Tòa nhà JobNest Tower',
                            'la_dia_diem_chinh' => 1,
                        ]);
                    }
                    if ($industryId) {
                        DB::table('job_industries')->insertOrIgnore([
                            'job_id' => $jobId, 'industry_id' => $industryId,
                        ]);
                    }
                    foreach (array_slice($skillIds, 0, 3) as $sid) {
                        DB::table('job_skills')->insertOrIgnore([
                            'job_id' => $jobId, 'skill_id' => $sid,
                        ]);
                    }
                }
                $jobIds[] = $jobId;
            }

            // 4. Tạo 6 ứng viên mẫu (users + candidates + kinh nghiệm + học vấn + kỹ năng + cvs)
            $candidateDefs = [
                [
                    'email' => 'nguyen.minh.anh@demo.test', 'phone' => '0901234561',
                    'ho_ten' => 'Nguyễn Minh Anh', 'gioi_tinh' => 'nu', 'exp' => 3.0,
                    'hoc_van' => 'dai_hoc', 'luong' => 20000000, 'dia_chi' => '123 Nguyễn Huệ, P. Bến Nghé',
                    'cong_ty_cu' => 'Công ty TNHH Công Nghệ Sao Việt', 'vi_tri_cu' => 'Backend Developer (Laravel)',
                    'truong' => 'ĐH Công nghệ Thông tin - ĐHQG', 'nganh' => 'Kỹ thuật Phần mềm',
                ],
                [
                    'email' => 'tran.quoc.bao@demo.test', 'phone' => '0912345672',
                    'ho_ten' => 'Trần Quốc Bảo', 'gioi_tinh' => 'nam', 'exp' => 2.5,
                    'hoc_van' => 'dai_hoc', 'luong' => 18000000, 'dia_chi' => '45 Lê Lợi, P. Bến Thành',
                    'cong_ty_cu' => 'FPT Software', 'vi_tri_cu' => 'Fullstack Web Developer',
                    'truong' => 'ĐH Bách Khoa', 'nganh' => 'Khoa học Máy tính',
                ],
                [
                    'email' => 'le.thu.ha@demo.test', 'phone' => '0987654323',
                    'ho_ten' => 'Lê Thu Hà', 'gioi_tinh' => 'nu', 'exp' => 1.5,
                    'hoc_van' => 'cao_dang', 'luong' => 14000000, 'dia_chi' => '78 Cách Mạng Tháng 8',
                    'cong_ty_cu' => 'Haravan', 'vi_tri_cu' => 'Frontend Developer',
                    'truong' => 'Cao đẳng FPT Polytechnic', 'nganh' => 'Lập trình Web',
                ],
                [
                    'email' => 'pham.hoang.nam@demo.test', 'phone' => '0938112234',
                    'ho_ten' => 'Phạm Hoàng Nam', 'gioi_tinh' => 'nam', 'exp' => 4.0,
                    'hoc_van' => 'dai_hoc', 'luong' => 26000000, 'dia_chi' => '210 Võ Văn Tần',
                    'cong_ty_cu' => 'VNG Corporation', 'vi_tri_cu' => 'Senior Business Analyst',
                    'truong' => 'ĐH Kinh tế TP.HCM', 'nganh' => 'Hệ thống thông tin quản lý',
                ],
                [
                    'email' => 'vo.ngoc.trinh@demo.test', 'phone' => '0977889905',
                    'ho_ten' => 'Võ Ngọc Trinh', 'gioi_tinh' => 'nu', 'exp' => 2.0,
                    'hoc_van' => 'dai_hoc', 'luong' => 16000000, 'dia_chi' => '19 Pasteur',
                    'cong_ty_cu' => 'Tiki Corporation', 'vi_tri_cu' => 'QC / Manual Tester',
                    'truong' => 'ĐH Khoa học Tự nhiên', 'nganh' => 'Công nghệ Thông tin',
                ],
                [
                    'email' => 'dang.huu.phuc@demo.test', 'phone' => '0966554416',
                    'ho_ten' => 'Đặng Hữu Phúc', 'gioi_tinh' => 'nam', 'exp' => 5.0,
                    'hoc_van' => 'sau_dai_hoc', 'luong' => 30000000, 'dia_chi' => '88 Điện Biên Phủ',
                    'cong_ty_cu' => 'Momo (M_Service)', 'vi_tri_cu' => 'Lead Business Analyst',
                    'truong' => 'ĐH Quốc tế', 'nganh' => 'Quản trị Hệ thống Thông tin',
                ],
            ];

            $candidates = []; // [['candidate_id' => ..., 'cv_id' => ...], ...]
            foreach ($candidateDefs as $c) {
                $user = DB::table('users')->where('email', $c['email'])->first();
                $userId = $user ? $user->id : DB::table('users')->insertGetId([
                    'email'             => $c['email'],
                    'phone'             => $c['phone'],
                    'password_hash'     => Hash::make('Password@123'),
                    'role'              => 'candidate',
                    'status'            => 'active',
                    'email_verified_at' => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);

                $cand = DB::table('candidates')->where('user_id', $userId)->first();
                if ($cand) {
                    $candidateId = $cand->id;
                } else {
                    $candidateId = DB::table('candidates')->insertGetId([
                        'user_id'              => $userId,
                        'ho_ten'               => $c['ho_ten'],
                        'ngay_sinh'            => '1998-05-15',
                        'gioi_tinh'            => $c['gioi_tinh'],
                        'province_id'          => $provinceId,
                        'district_id'          => $districtId,
                        'dia_chi_chi_tiet'     => $c['dia_chi'],
                        'muc_tieu_nghe_nghiep' => 'Tìm kiếm môi trường chuyên nghiệp để phát huy kỹ năng và đóng góp giá trị cho sản phẩm.',
                        'so_nam_kinh_nghiem'   => $c['exp'],
                        'trinh_do_hoc_van'     => $c['hoc_van'],
                        'muc_luong_mong_muon'  => $c['luong'],
                        'trang_thai_tim_viec'  => 'san_sang_phong_van',
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ]);

                    DB::table('candidate_experiences')->insert([
                        'candidate_id'     => $candidateId,
                        'ten_cong_ty'      => $c['cong_ty_cu'],
                        'vi_tri_cong_viec' => $c['vi_tri_cu'],
                        'tu_thang'         => '2023-03-01',
                        'den_thang'        => null,
                        'dang_lam_viec'    => 1,
                        'mo_ta_cong_viec'  => 'Tham gia phát triển tính năng lõi, tối ưu hiệu năng truy vấn và làm việc theo quy trình Agile/Scrum.',
                    ]);

                    DB::table('candidate_educations')->insert([
                        'candidate_id' => $candidateId,
                        'ten_truong'   => $c['truong'],
                        'chuyen_nganh' => $c['nganh'],
                        'bang_cap'     => 'Cử nhân',
                        'tu_nam'       => 2017,
                        'den_nam'      => 2021,
                        'dang_hoc'     => 0,
                        'mo_ta'        => 'Tốt nghiệp loại Khá/Giỏi.',
                    ]);

                    foreach (array_slice($skillIds, 0, 3) as $sid) {
                        DB::table('candidate_skills')->insertOrIgnore([
                            'candidate_id' => $candidateId,
                            'skill_id'     => $sid,
                            'muc_do'       => 'thanh_thao',
                        ]);
                    }
                }

                $cv = DB::table('cvs')->where('candidate_id', $candidateId)->first();
                $cvId = $cv ? $cv->id : DB::table('cvs')->insertGetId([
                    'candidate_id'  => $candidateId,
                    'ten_cv'        => 'CV_' . Str::slug($c['ho_ten'], '_') . '.pdf',
                    'loai'          => 'tai_len',
                    'file_url'      => $demoCvPath,
                    'noi_dung_json' => null,
                    'cong_khai'     => 1,
                    'is_default'    => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                $candidates[] = ['candidate_id' => $candidateId, 'cv_id' => $cvId];
            }

            // 5. Tạo các đơn ứng tuyển rải đều vào 4 tin (cả tin Full CV và tin Khóa CV)
            // [jobIndex (0..3), candidateIndex (0..5), trang_thai, ghi_chu_noi_bo]
            $appMatrix = [
                // Tin #1 (Full CV)
                [0, 0, 'moi_phong_van', 'Ứng viên kinh nghiệm Laravel tốt, hẹn PV thứ 5 lúc 14h.'],
                [0, 1, 'da_nop', null],
                [0, 2, 'tu_choi', 'Chưa đủ kinh nghiệm Backend theo yêu cầu.'],
                // Tin #2 (Full CV)
                [1, 2, 'phu_hop', 'Portfolio UI rất đẹp, chuyển Tech Lead xem thêm.'],
                [1, 1, 'dang_xem_xet', 'Đang đối chiếu mức lương mong muốn.'],
                // Tin #3 (Full CV)
                [2, 4, 'da_tuyen', 'Đã gửi Offer và ứng viên đồng ý nhận việc.'],
                // Tin #4 (KHÓA CV - quyen_xem_cv_day_du = 0) -> Dùng để test Che thông tin!
                [3, 3, 'da_nop', null],
                [3, 5, 'dang_xem_xet', 'Hồ sơ Lead BA rất sáng, cần nâng cấp gói để lấy SĐT liên hệ.'],
                [3, 0, 'da_nop', null],
            ];

            foreach ($appMatrix as [$jIdx, $cIdx, $status, $note]) {
                $jobId       = $jobIds[$jIdx];
                $candidateId = $candidates[$cIdx]['candidate_id'];
                $cvId        = $candidates[$cIdx]['cv_id'];

                $exists = DB::table('applications')
                    ->where('job_id', $jobId)
                    ->where('candidate_id', $candidateId)
                    ->exists();

                if (!$exists) {
                    $appId = DB::table('applications')->insertGetId([
                        'job_id'          => $jobId,
                        'candidate_id'    => $candidateId,
                        'cv_id'           => $cvId,
                        'thu_gioi_thieu'  => 'Kính gửi Quý công ty, tôi rất ấn tượng với vị trí đang tuyển và mong muốn được trao đổi thêm trong buổi phỏng vấn.',
                        'trang_thai'      => $status,
                        'ghi_chu_noi_bo'  => $note,
                        'ngay_ung_tuyen'  => $now->copy()->subDays(rand(1, 4)),
                        'ngay_cap_nhat'   => $now,
                    ]);

                    if ($status !== 'da_nop') {
                        DB::table('application_status_history')->insert([
                            'application_id' => $appId,
                            'trang_thai_cu'  => 'da_nop',
                            'trang_thai_moi' => $status,
                            'employer_id'    => $employer->id,
                            'ghi_chu'        => $note,
                            'created_at'     => $now,
                        ]);
                    }
                }
            }

            // Đồng bộ lại cột luot_ung_tuyen trên bảng jobs
            foreach ($jobIds as $jobId) {
                $count = DB::table('applications')->where('job_id', $jobId)->count();
                DB::table('jobs')->where('id', $jobId)->update(['luot_ung_tuyen' => $count]);
            }
        });

        $this->command->info('Đã seed xong dữ liệu demo: 4 tin tuyển dụng (3 Full CV + 1 Khóa CV), 6 ứng viên và 9 hồ sơ ứng tuyển!');
    }
}
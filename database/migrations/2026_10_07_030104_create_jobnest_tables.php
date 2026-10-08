<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //NHÓM 1: DANH MỤC DÙNG CHUNG
        Schema::create('provinces', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_tinh', 100);
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('province_id');
            $table->string('ten_quan_huyen', 100);
            $table->foreign('province_id')->references('id')->on('provinces');
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_nganh', 150);
            $table->unsignedInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('industries');
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_ky_nang', 100)->unique();
        });

        //NHÓM 2: TÀI KHOẢN & PHÂN QUYỀN
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->unique();
            $table->string('phone', 20)->unique()->nullable();
            $table->string('password_hash', 255);
            $table->enum('role', ['admin', 'employer', 'candidate']);
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->string('avatar_url', 255)->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('ho_ten', 150);
            $table->timestamp('created_at')->useCurrent();
        });
        
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('ten_cong_ty', 255);
            $table->string('slug', 255)->unique();
            $table->mediumText('mo_ta')->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->string('cover_image_url', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->enum('quy_mo', ['duoi_25', '25_99', '100_499', '500_999', 'tren_1000'])->nullable();
            $table->unsignedInteger('industry_id')->nullable();
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('dia_chi_chi_tiet', 255)->nullable();
            $table->string('email_lien_he', 150)->nullable();
            $table->string('hotline', 20)->nullable();
            $table->string('ma_so_thue', 20)->unique();
            $table->enum('trang_thai', ['cho_duyet', 'da_duyet', 'tu_choi', 'tam_khoa'])->default('cho_duyet');
            $table->string('ly_do_tu_choi', 255)->nullable();
            $table->unsignedInteger('so_nguoi_theo_doi')->default(0);
            $table->timestamps();

            $table->foreign('industry_id')->references('id')->on('industries');
            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('district_id')->references('id')->on('districts');
            $table->fullText('ten_cong_ty', 'ft_company_name');
        });

        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('ho_ten', 150);
            $table->string('chuc_vu', 100)->nullable();
            $table->boolean('la_quan_tri_cong_ty')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('ho_ten', 150);
            $table->date('ngay_sinh')->nullable();
            $table->enum('gioi_tinh', ['nam', 'nu', 'khac'])->nullable();
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->string('dia_chi_chi_tiet', 255)->nullable();
            $table->text('muc_tieu_nghe_nghiep')->nullable();
            $table->decimal('so_nam_kinh_nghiem', 3, 1)->nullable();
            $table->enum('trinh_do_hoc_van', ['trung_hoc', 'trung_cap', 'cao_dang', 'dai_hoc', 'sau_dai_hoc'])->nullable();
            $table->decimal('muc_luong_mong_muon', 12, 0)->nullable();
            $table->enum('trang_thai_tim_viec', ['dang_tim_viec', 'san_sang_phong_van', 'khong_tim_viec'])->default('dang_tim_viec');
            $table->timestamps();

            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('district_id')->references('id')->on('districts');
        });

        //NHÓM 3: HỒ SƠ CHI TIẾT ỨNG VIÊN
        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('ten_truong', 255);
            $table->string('chuyen_nganh', 255)->nullable();
            $table->string('bang_cap', 100)->nullable();
            $table->year('tu_nam');
            $table->year('den_nam')->nullable();
            $table->boolean('dang_hoc')->default(0);
            $table->text('mo_ta')->nullable();
        });

        Schema::create('candidate_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('ten_cong_ty', 255);
            $table->string('vi_tri_cong_viec', 255);
            $table->date('tu_thang');
            $table->date('den_thang')->nullable();
            $table->boolean('dang_lam_viec')->default(0);
            $table->text('mo_ta_cong_viec')->nullable();
        });

        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->unsignedInteger('skill_id');
            $table->enum('muc_do', ['co_ban', 'trung_binh', 'thanh_thao', 'chuyen_gia'])->default('trung_binh');
            $table->primary(['candidate_id', 'skill_id']);
            $table->foreign('skill_id')->references('id')->on('skills');
        });

        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates');
            $table->string('ten_cv', 150);
            $table->enum('loai', ['tai_len', 'tao_tren_he_thong']);
            $table->string('file_url', 255)->nullable();
            $table->json('noi_dung_json')->nullable();
            $table->boolean('cong_khai')->default(1);
            $table->boolean('is_default')->default(0);
            $table->unsignedBigInteger('default_group')->nullable()->storedAs('IF(is_default = 1, candidate_id, NULL)');
            $table->timestamps();

            $table->unique('default_group', 'uq_cv_one_default_per_candidate');
            $table->index(['candidate_id', 'is_default'], 'idx_cvs_candidate_default');
        });

        //NHÓM 6: GÓI DỊCH VỤ & THANH TOÁN
        Schema::create('packages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_goi', 100);
            $table->enum('loai', ['dang_tin', 'xem_cv']);
            $table->decimal('gia', 12, 0);
            $table->integer('so_ngay_hieu_luc');
            $table->integer('so_luot');
            $table->boolean('hien_thi_noi_bat')->default(0);
            $table->text('mo_ta')->nullable();
            $table->boolean('dang_ban')->default(1);
        });

        Schema::create('company_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->unsignedInteger('package_id');
            $table->decimal('so_tien', 12, 0);
            $table->enum('phuong_thuc_thanh_toan', ['chuyen_khoan', 'vi_dien_tu', 'the_tin_dung']);
            $table->string('ma_giao_dich', 100)->nullable();
            $table->enum('trang_thai_thanh_toan', ['cho_thanh_toan', 'da_thanh_toan', 'that_bai'])->default('cho_thanh_toan');
            $table->integer('so_luot_con_lai');
            $table->timestamp('ngay_mua')->useCurrent();
            $table->dateTime('ngay_het_han');
            $table->foreign('package_id')->references('id')->on('packages');
        });

        //NHÓM 4: TIN TUYỂN DỤNG
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('employer_id')->constrained('employers');
            $table->string('tieu_de', 255);
            $table->string('slug', 255)->unique();
            $table->mediumText('mo_ta_cong_viec');
            $table->mediumText('yeu_cau_ung_vien')->nullable();
            $table->mediumText('quyen_loi')->nullable();
            $table->enum('loai_hinh_lam_viec', ['toan_thoi_gian', 'ban_thoi_gian', 'thuc_tap', 'tu_do', 'tu_xa']);
            $table->enum('cap_bac', ['thuc_tap_sinh', 'nhan_vien', 'truong_nhom', 'quan_ly', 'truong_phong', 'giam_doc']);
            $table->integer('kinh_nghiem_toi_thieu')->nullable();
            $table->decimal('luong_toi_thieu', 12, 0)->nullable();
            $table->decimal('luong_toi_da', 12, 0)->nullable();
            $table->boolean('luong_thoa_thuan')->default(0);
            $table->integer('so_luong_tuyen')->default(1);
            $table->date('han_nop_ho_so');
            $table->enum('trang_thai', ['nhap', 'cho_duyet', 'dang_tuyen', 'het_han', 'da_dong', 'tu_choi'])->default('nhap');
            $table->string('ly_do_tu_choi', 255)->nullable();
            $table->foreignId('company_order_id')->nullable()->constrained('company_orders');
            $table->unsignedInteger('luot_xem')->default(0);
            $table->unsignedInteger('luot_ung_tuyen')->default(0);
            $table->dateTime('ngay_dang')->nullable();
            $table->timestamps();

            $table->fullText(['tieu_de', 'mo_ta_cong_viec'], 'ft_job_search');
            $table->index(['trang_thai', 'han_nop_ho_so'], 'idx_jobs_status_deadline');
            $table->index('company_id', 'idx_jobs_company');
        });

        Schema::create('job_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->unsignedInteger('province_id');
            $table->unsignedInteger('district_id')->nullable();
            $table->string('dia_chi_chi_tiet', 255)->nullable();
            $table->boolean('la_dia_diem_chinh')->default(0);

            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('district_id')->references('id')->on('districts');
            $table->index(['province_id', 'district_id'], 'idx_joblocations_filter');
        });

        Schema::create('job_industries', function (Blueprint $table) {
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->unsignedInteger('industry_id');
            $table->primary(['job_id', 'industry_id']);
            $table->foreign('industry_id')->references('id')->on('industries');
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->unsignedInteger('skill_id');
            $table->primary(['job_id', 'skill_id']);
            $table->foreign('skill_id')->references('id')->on('skills');
        });

        //NHÓM 5,7,8: ỨNG TUYỂN, TƯƠNG TÁC, UY TÍN, THÔNG BÁO
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs');
            $table->foreignId('candidate_id')->constrained('candidates');
            $table->foreignId('cv_id')->constrained('cvs');
            $table->text('thu_gioi_thieu')->nullable();
            $table->enum('trang_thai', ['da_nop', 'dang_xem_xet', 'phu_hop', 'moi_phong_van', 'tu_choi', 'da_tuyen'])->default('da_nop');
            $table->text('ghi_chu_noi_bo')->nullable();
            $table->timestamp('ngay_ung_tuyen')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['job_id', 'candidate_id'], 'uq_app_job_candidate');
            $table->index(['job_id', 'trang_thai'], 'idx_applications_job_status');
            $table->index('candidate_id', 'idx_applications_candidate');
        });

        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('trang_thai_cu', 50)->nullable();
            $table->string('trang_thai_moi', 50);
            $table->foreignId('employer_id')->nullable()->constrained('employers');
            $table->string('ghi_chu', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['candidate_id', 'job_id']);
        });

        Schema::create('followed_companies', function (Blueprint $table) {
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['candidate_id', 'company_id']);
        });

        Schema::create('job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('tu_khoa', 255)->nullable();
            $table->unsignedInteger('industry_id')->nullable();
            $table->unsignedInteger('province_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->decimal('muc_luong_tu', 12, 0)->nullable();
            $table->enum('tan_suat', ['hang_ngay', 'hang_tuan'])->default('hang_tuan');
            $table->boolean('dang_hoat_dong')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('industry_id')->references('id')->on('industries');
            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('district_id')->references('id')->on('districts');
        });

        Schema::create('cv_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers');
            $table->foreignId('candidate_id')->constrained('candidates');
            $table->foreignId('cv_id')->nullable()->constrained('cvs');
            $table->foreignId('company_order_id')->nullable()->constrained('company_orders');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('company_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('candidate_id')->constrained('candidates');
            $table->unsignedTinyInteger('diem_danh_gia');
            $table->string('tieu_de', 255)->nullable();
            $table->text('noi_dung');
            $table->enum('trang_thai', ['cho_duyet', 'da_duyet', 'tu_choi'])->default('cho_duyet');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['company_id', 'candidate_id'], 'uq_review_once');
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->morphs('reportable'); // Tự động sinh reportable_type, reportable_id và index
            $table->string('ly_do', 255);
            $table->text('mo_ta')->nullable();
            $table->enum('trang_thai', ['cho_xu_ly', 'da_xu_ly', 'bo_qua'])->default('cho_xu_ly');
            $table->foreignId('admin_id')->nullable()->constrained('admins');
            $table->text('ghi_chu_xu_ly')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('ngay_xu_ly')->nullable();

            $table->index('trang_thai', 'idx_reports_status');
        });


        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('loai', ['ho_so_cap_nhat', 'tin_moi_phu_hop', 'tin_duyet', 'tin_tu_choi', 'cong_ty_duyet', 'danh_gia_duyet', 'he_thong']);
            $table->string('tieu_de', 255);
            $table->text('noi_dung')->nullable();
            $table->string('lien_ket', 255)->nullable();
            $table->boolean('da_doc')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'da_doc'], 'idx_notifications_user_unread');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('company_reviews');
        Schema::dropIfExists('cv_views');
        Schema::dropIfExists('job_alerts');
        Schema::dropIfExists('followed_companies');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('job_industries');
        Schema::dropIfExists('job_locations');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('company_orders');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('cvs');
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('candidate_experiences');
        Schema::dropIfExists('candidate_educations');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('users');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
    }
};
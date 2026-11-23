<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. CẬP NHẬT BẢNG `packages`
        Schema::table('packages', function (Blueprint $table) {
            $table->string('ma_goi', 30)->nullable()->unique()->after('id');
            $table->unsignedInteger('so_tin_xem_cv_day_du')->nullable()->after('so_luot');
            $table->unsignedInteger('so_tin_uu_tien')->nullable()->after('so_tin_xem_cv_day_du');
            $table->unsignedInteger('so_tin_hot')->nullable()->after('so_tin_uu_tien');
        });

        // Dùng lệnh SQL thô để sửa ENUM và cho phép NULL an toàn nhất
        DB::statement("ALTER TABLE packages MODIFY loai ENUM('dang_tin','xem_cv','goi_dich_vu') NOT NULL");
        DB::statement('ALTER TABLE packages MODIFY so_ngay_hieu_luc INT NULL');

        // 2. CẬP NHẬT BẢNG `company_orders`
        Schema::table('company_orders', function (Blueprint $table) {
            $table->json('gioi_han_snapshot')->nullable()->after('so_luot_con_lai');
            $table->unsignedInteger('da_dung_tin_xem_cv')->default(0)->after('gioi_han_snapshot');
            $table->unsignedInteger('da_dung_tin_uu_tien')->default(0)->after('da_dung_tin_xem_cv');
            $table->unsignedInteger('da_dung_tin_hot')->default(0)->after('da_dung_tin_uu_tien');
            
            // Đánh index để truy vấn các gói đang kích hoạt của công ty cho nhanh
            $table->index(['company_id', 'trang_thai_thanh_toan', 'ngay_het_han'], 'idx_active_orders');
        });

        DB::statement("ALTER TABLE company_orders MODIFY phuong_thuc_thanh_toan ENUM('chuyen_khoan','vi_dien_tu','the_tin_dung') NULL");
        DB::statement('ALTER TABLE company_orders MODIFY ngay_het_han DATETIME NULL');
        DB::statement('ALTER TABLE company_orders MODIFY so_luot_con_lai INT NULL');

        // 3. CẬP NHẬT BẢNG `jobs`
        Schema::table('jobs', function (Blueprint $table) {
            $table->boolean('quyen_xem_cv_day_du')->default(false)->after('trang_thai');
            $table->dateTime('highlight_until')->nullable()->after('quyen_xem_cv_day_du');
            $table->dateTime('urgent_until')->nullable()->after('highlight_until');
            $table->dateTime('hot_until')->nullable()->after('urgent_until');
            $table->dateTime('pushed_at')->nullable()->after('hot_until');
            
            // Đánh index phục vụ thuật toán sắp xếp (Ranking) ở trang chủ
            $table->index(['hot_until', 'pushed_at', 'created_at'], 'idx_jobs_ranking');
        });

        // 4. KHỞI TẠO TỰ ĐỘNG GÓI FREE VÀO DATABASE
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
                'dang_ban'             => 1,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Phục hồi lại bảng jobs
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_ranking');
            $table->dropColumn(['quyen_xem_cv_day_du', 'highlight_until', 'urgent_until', 'hot_until', 'pushed_at']);
        });

        // Phục hồi dữ liệu để tránh lỗi NOT NULL khi rollback company_orders
        DB::statement("UPDATE company_orders SET phuong_thuc_thanh_toan = 'chuyen_khoan' WHERE phuong_thuc_thanh_toan IS NULL");
        DB::statement("UPDATE company_orders SET ngay_het_han = '2099-12-31 00:00:00' WHERE ngay_het_han IS NULL");
        DB::statement('UPDATE company_orders SET so_luot_con_lai = 0 WHERE so_luot_con_lai IS NULL');
        
        DB::statement("ALTER TABLE company_orders MODIFY phuong_thuc_thanh_toan ENUM('chuyen_khoan','vi_dien_tu','the_tin_dung') NOT NULL");
        DB::statement('ALTER TABLE company_orders MODIFY ngay_het_han DATETIME NOT NULL');
        DB::statement('ALTER TABLE company_orders MODIFY so_luot_con_lai INT NOT NULL');

        Schema::table('company_orders', function (Blueprint $table) {
            $table->dropIndex('idx_active_orders');
            $table->dropColumn(['gioi_han_snapshot', 'da_dung_tin_xem_cv', 'da_dung_tin_uu_tien', 'da_dung_tin_hot']);
        });

        // Phục hồi dữ liệu để tránh lỗi NOT NULL khi rollback packages
        DB::statement("UPDATE packages SET loai = 'dang_tin' WHERE loai = 'goi_dich_vu'");
        DB::statement('UPDATE packages SET so_ngay_hieu_luc = 0 WHERE so_ngay_hieu_luc IS NULL');
        
        DB::statement("ALTER TABLE packages MODIFY loai ENUM('dang_tin','xem_cv') NOT NULL");
        DB::statement('ALTER TABLE packages MODIFY so_ngay_hieu_luc INT NOT NULL');

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['ma_goi', 'so_tin_xem_cv_day_du', 'so_tin_uu_tien', 'so_tin_hot']);
        });
    }
};
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanEmployerApplicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Gom toàn bộ ID của dữ liệu mẫu
            $demoUserIds = DB::table('users')
                ->where('email', 'like', '%@demo.test')
                ->pluck('id');

            $demoCandidateIds = DB::table('candidates')
                ->whereIn('user_id', $demoUserIds)
                ->pluck('id');

            $demoCvIds = DB::table('cvs')
                ->whereIn('candidate_id', $demoCandidateIds)
                ->pluck('id');

            $demoJobIds = DB::table('jobs')
                ->where('slug', 'like', 'demo-%')
                ->pluck('id');

            $demoOrderIds = DB::table('company_orders')
                ->where('ma_giao_dich', 'like', 'FREE-DEMO-%')
                ->pluck('id');

            // 2. Xóa bảng con cấp thấp nhất (cv_views, applications)
            // Lưu ý: application_status_history tự xóa nhờ ON DELETE CASCADE
            DB::table('cv_views')
                ->whereIn('candidate_id', $demoCandidateIds)
                ->orWhereIn('cv_id', $demoCvIds)
                ->orWhereIn('company_order_id', $demoOrderIds)
                ->delete();

            DB::table('applications')
                ->whereIn('job_id', $demoJobIds)
                ->orWhereIn('candidate_id', $demoCandidateIds)
                ->orWhereIn('cv_id', $demoCvIds)
                ->delete();

            // 3. Xóa tin tuyển dụng mẫu (job_locations, job_industries, job_skills tự xóa theo CASCADE)
            DB::table('jobs')->whereIn('id', $demoJobIds)->delete();

            // Phòng trường hợp bạn có tạo thêm tin thật gắn với đơn FREE-DEMO -> gỡ liên kết trước khi xóa đơn
            DB::table('jobs')
                ->whereIn('company_order_id', $demoOrderIds)
                ->update(['company_order_id' => null]);

            // 4. Xóa đơn hàng gói Free mẫu
            DB::table('company_orders')->whereIn('id', $demoOrderIds)->delete();

            // 5. Xóa CV mẫu (bảng cvs không có ON DELETE CASCADE khi xóa candidates)
            DB::table('cvs')->whereIn('id', $demoCvIds)->delete();

            // 6. Xóa hồ sơ ứng viên & tài khoản user mẫu
            // (candidate_experiences, candidate_educations, candidate_skills tự xóa theo CASCADE)
            DB::table('candidates')->whereIn('id', $demoCandidateIds)->delete();
            DB::table('users')->whereIn('id', $demoUserIds)->delete();
            //DB::table('packages')->where('ma_goi', 'pro_30')->delete();
        });

        // 7. Xóa file PDF mẫu trong storage
        Storage::disk('local')->delete('cvs/demo-candidate-cv.pdf');

        $this->command->info('Đã dọn sạch toàn bộ dữ liệu demo (giữ nguyên dữ liệu thật và bảng packages).');
    }
}

//php artisan db:seed --class=CleanEmployerApplicationDemoSeeder
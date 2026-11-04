<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class VietnamZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('districts')->truncate();
        DB::table('provinces')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Đang tải dữ liệu Tỉnh/Thành & Quận/Huyện từ Open API...');

        $response = Http::withoutVerifying()->timeout(30)->get('https://provinces.open-api.vn/api/?depth=3');

        if ($response->successful()) {
            $data = $response->json();

            foreach ($data as $prov) {
                
                DB::table('provinces')->insert([
                    'id'   => $prov['code'],
                    'ten_tinh' => $prov['name'], 
                ]);

                
                $districts = [];
                foreach ($prov['districts'] as $dist) {
                    $districts[] = [
                        'id'          => $dist['code'],
                        'province_id' => $prov['code'],
                        'ten_quan_huyen'        => $dist['name'], 
                    ];
                }
                DB::table('districts')->insert($districts);
            }
            
            $this->command->info('Tuyệt vời! Đã nạp thành công toàn bộ dữ liệu địa giới hành chính.');
        } else {
            $this->command->error('Lỗi: Không thể kết nối đến API lấy dữ liệu.');
        }
    }
}
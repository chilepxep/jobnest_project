<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $table = 'jobs';

    protected $fillable = [
        'company_id',
         'employer_id', 
         'tieu_de', 
         'slug', 
         'mo_ta_cong_viec',
        'yeu_cau_ung_vien',
         'quyen_loi', 
         'loai_hinh_lam_viec',
          'cap_bac',
        'kinh_nghiem_toi_thieu', 
        'luong_toi_thieu', 
        'luong_toi_da',
        'luong_thoa_thuan',
        'so_luong_tuyen',
         'han_nop_ho_so',
         'trang_thai',
        'ly_do_tu_choi',
         'company_order_id',
          'luot_xem',
           'luot_ung_tuyen',
           'quyen_xem_cv_day_du', 
           'highlight_until', 
           'urgent_until', 
           'hot_until', 
           'pushed_at',
            'ngay_dang'
    ];

    protected $casts = [
    'luong_thoa_thuan'    => 'boolean',
    'han_nop_ho_so'       => 'date',
    'quyen_xem_cv_day_du' => 'boolean',
    'highlight_until'     => 'datetime',
    'urgent_until'        => 'datetime',
    'hot_until'           => 'datetime',
    'pushed_at'           => 'datetime',
    'ngay_dang'           => 'datetime',
    ];

    // Quan hệ: 1 Job thuộc về 1 Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Quan hệ: 1 Job do 1 Employer đăng
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    // Quan hệ 1-N: 1 Job có thể có nhiều Địa điểm (JobLocation)
    public function locations()
    {
        return $this->hasMany(JobLocation::class, 'job_id');
    }

    // Quan hệ N-N: Job và Industry (thông qua bảng trung gian job_industries)
    public function industries()
    {
        return $this->belongsToMany(Industry::class, 'job_industries', 'job_id', 'industry_id');
    }

    // Quan hệ N-N: Job và Skill (thông qua bảng trung gian job_skills)
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_skills', 'job_id', 'skill_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

     public function companyOrder()
    {
        return $this->belongsTo(CompanyOrder::class, 'company_order_id');
    }
}
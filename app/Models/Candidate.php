<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    public const EDUCATION_LABELS = [
       'trung_hoc'   => 'Trung học',
        'trung_cap'   => 'Trung cấp',
        'cao_dang'    => 'Cao đẳng',
        'dai_hoc'     => 'Đại học',
        'sau_dai_hoc' => 'Sau đại học',
    ];

    public const JOB_SEARCH_STATUSES = [
        'dang_tim_viec'      => 'Đang tìm việc',
        'san_sang_phong_van' => 'Sẵn sàng phỏng vấn',
        'khong_tim_viec'     => 'Không tìm việc',
    ];

    protected $fillable = [
        'user_id',
        'ho_ten',
        'ngay_sinh',
        'gioi_tinh',
        'province_id',
        'district_id',
        'dia_chi_chi_tiet',
        'muc_tieu_nghe_nghiep',
        'so_nam_kinh_nghiem',
        'trinh_do_hoc_van',
        'muc_luong_mong_muon',
        'trang_thai_tim_viec',
    ];

    protected $casts = [
        'ngay_sinh'           => 'date',
        'so_nam_kinh_nghiem'  => 'float',
        'muc_luong_mong_muon' => 'integer',
    ];

    public function user(): BelongsTo 
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo 
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo 
    {
        return $this->belongsTo(District::class);
    }

    public function cvs(): HasMany 
    {
        return $this->hasMany(Cv::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CandidateExperience::class)
            ->orderByDesc('dang_lam_viec')
            ->orderByDesc('tu_thang');
    }

    public function educations(): HasMany 
    {
        return $this->hasMany(CandidateEducation::class)
        ->orderByDesc('dang_hoc')
        ->orderByDesc('tu_nam');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skills')
        ->withPivot('muc_do');
    }

}
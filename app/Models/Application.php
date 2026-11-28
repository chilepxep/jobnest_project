<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    public const CREATED_AT = 'ngay_ung_tuyen';
    public const UPDATED_AT = 'ngay_cap_nhat';

    public const STATUSES = [
        'da_nop' => ['label' => 'Mới nộp', 'badge' => 'bg-label-secondary'],
        'dang_xem_xet'  => ['label' => 'Đang xem xét',   'badge' => 'bg-label-info'],
        'phu_hop'       => ['label' => 'Phù hợp',        'badge' => 'bg-label-primary'],
        'moi_phong_van' => ['label' => 'Mời phỏng vấn',  'badge' => 'bg-label-warning'],
        'da_tuyen'      => ['label' => 'Đã tuyển',       'badge' => 'bg-label-success'],
        'tu_choi'       => ['label' => 'Từ chối',        'badge' => 'bg-label-danger'],
    ];

    protected $fillable = [
        'job_id',
        'candidate_id',
        'cv_id',
        'thu_gioi_thieu',
        'trang_thai',
        'ghi_chu_noi_bo',
    ];

    protected $casts = [
        'ngay_ung_tuyen' => 'datetime',
        'ngay_cap_nhat'  => 'datetime',
    ];
    
    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function cv(): BelongsTo 
    {
        return $this->belongsTo(Cv::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest('created_at');
    }

    // ------------------------------------------------------------------
    // Kiểm soát quyền xem Full CV & Che thông tin (Masking)
    // ------------------------------------------------------------------

    public function canViewFullContact(): bool
    {
        return (bool) $this->job?->quyen_xem_cv_day_du;
    }

    public function getDisplayEmailAttribute(): string
    {
        if (!$this->canViewFullContact()) {
            return '********@***** (Đã ẩn theo gói)';
        }

        return $this->candidate?->user?->email ?: 'Chưa cập nhật';
    }

    public function getDisplayPhoneAttribute(): string
    {
        if (!$this->canViewFullContact()) {
            return '********** (Đã ẩn theo gói)';
        }

        return $this->candidate?->user?->phone ?: 'Chưa cập nhật';
    }

    public function getDisplayAddressAttribute(): string
    {
        $candidate = $this->candidate;
        if (!$candidate) {
            return 'Chưa cập nhật';
        }

        $area = collect([
            $candidate->district?->ten_quan_huyen,
            $candidate->province?->ten_tinh,
        ])->filter()->implode(', ');

        // Khi bị khóa chỉ hiện Quận/Tỉnh, che số nhà & tên đường (dia_chi_chi_tiet)
        if (!$this->canViewFullContact()) {
            return $area ? "****** ($area)" : 'Đã ẩn địa chỉ chi tiết';
        }

        return collect([$candidate->dia_chi_chi_tiet, $area])->filter()->implode(', ') ?: 'Chưa cập nhật';
    }
}
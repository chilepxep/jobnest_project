<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateExperience extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'ten_cong_ty',
        'vi_tri_cong_viec',
        'tu_thang',
        'den_thang',
        'dang_lam_viec',
        'mo_ta_cong_viec',
    ];

    protected $casts = [
        'tu_thang'      => 'date',
        'den_thang'     => 'date',
        'dang_lam_viec' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
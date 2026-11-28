<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEducation extends Model
{

    protected $table = 'candidate_educations';

    public $timestamps = false;

    protected $fillable = [
        'candidate_id',
        'ten_truong',
        'chuyen_nganh',
        'bang_cap',
        'tu_nam',
        'den_nam',
        'dang_hoc',
        'mo_ta',
    ];

    protected $casts = [
        'tu_nam'   => 'integer',
        'den_nam'  => 'integer',
        'dang_hoc' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
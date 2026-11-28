<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cv extends Model
{
    protected $fillable = [
        'candidate_id',
        'ten_cv',
        'loai',
        'file_url',
        'noi_dung_json',
        'cong_khai',
        'is_default',
    ];

    protected $casts = [
        'noi_dung_json' => 'array',
        'cong_khai'     => 'boolean',
        'is_default'    => 'boolean',
    ];

    public function candidate():BelongsTo 
    {
        return $this->belongsTo(Candidate::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

}
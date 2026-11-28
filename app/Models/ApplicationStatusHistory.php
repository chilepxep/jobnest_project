<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    public const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'trang_thai_cu',
        'trang_thai_moi',
        'employer_id',
        'ghi_chu',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }
}
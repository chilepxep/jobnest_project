<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobLocation extends Model
{
    protected $table = 'job_locations';
    
    public $timestamps = false;

    protected $fillable = [
        'job_id', 'province_id', 'district_id', 'dia_chi_chi_tiet', 'la_dia_diem_chinh'
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
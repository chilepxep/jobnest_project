<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    public $timestamps = false; 

    protected $fillable = [
        'user_id', 'company_id', 'ho_ten', 'chuc_vu', 'la_quan_tri_cong_ty', 'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
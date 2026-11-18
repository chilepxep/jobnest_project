<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'ten_cong_ty', 'slug', 'mo_ta', 'logo_url', 'cover_image_url', 
        'website', 'quy_mo', 'industry_id', 'province_id', 'district_id', 
        'dia_chi_chi_tiet', 'email_lien_he', 'hotline', 'ma_so_thue', 
        'trang_thai', 'ly_do_tu_choi', 'so_nguoi_theo_doi'
    ];

    protected static function boot()
    {
        parent::boot();   
        static::creating(function ($company) {
            
            if (empty($company->slug)) {
                $base = Str::slug($company->ten_cong_ty) ?: 'cong-ty';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }

                $company->slug = $slug;
            }
        });
    }

    public function employers()
    {
        return $this->hasMany(Employer::class);
    }

}
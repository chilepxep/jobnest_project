<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Employer;
use Illuminate\Support\Facades\Auth;
use App\Models\Industry;
use App\Models\Province;
use App\Models\District;

class EmployerAuthController extends Controller
{
    public function showRegisterStep1()
     {
        return view('auth.employer-step1'); 
     }

    public function processStep1(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|unique:users,email|max:150',
            'password' => 'required|min:6|confirmed',
            'phone'    => 'nullable|string|max:20|unique:users,phone',
        ], [
            'email.required'     => 'Vui lòng nhập địa chỉ email.',
            'email.email'        => 'Định dạng email không hợp lệ.',
            'email.unique'       => 'Email này đã tồn tại trong hệ thống.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.min'       => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.', 
            'phone.unique'       => 'Số điện thoại này đã được sử dụng.'
        ]);

        $user = User::create([
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password_hash' => Hash::make($request->password),
            'role'          => 'employer',
            'status'        => 'active',
        ]);

        Auth::login($user);
        $user->update(['last_login_at' => now()]);

        return redirect()->route('employer.register.step2');
    }

    public function showRegisterStep2()
    {
        // Chặn nếu đã có công ty rồi thì không cho vào lại trang này
        if (Auth::user()->employer) {
            return redirect()->route('employer.dashboard'); // Middleware sẽ tự nắn dòng nếu chưa duyệt
        }

        $industries = \App\Models\Industry::orderBy('ten_nganh', 'asc')->get(); 
        $provinces = \App\Models\Province::orderBy('ten_tinh', 'asc')->get();
        return view('auth.employer-step2', compact('industries', 'provinces'));
    }

    public function processStep2(Request $request)
    {
        $request->validate([
            //Thông tin cá nhân HR 
            'ho_ten'           => 'required|string|max:150',
            'chuc_vu'          => 'required|string|max:100',
            
            //Thông tin cơ bản Công ty
            'ten_cong_ty'      => 'required|string|max:255',
            'ma_so_thue'       => 'required|string|max:20|unique:companies,ma_so_thue',
            'mo_ta'            => 'required|string',
            'quy_mo'           => 'required|in:duoi_25,25_99,100_499,500_999,tren_1000',
            'industry_id'      => 'required|integer|exists:industries,id',
            'province_id'      => 'required|integer|exists:provinces,id',
            'district_id'      => 'nullable|integer|exists:districts,id',
            'dia_chi_chi_tiet' => 'required|string|max:255',
            'email_lien_he'    => 'required|email|max:150',
            'hotline'          => 'required|string|max:20',
            'website'          => 'nullable|url|max:255',
        ]);

        try {
            DB::beginTransaction();

            $company = Company::create([
                'ten_cong_ty'      => $request->ten_cong_ty,
                'ma_so_thue'       => $request->ma_so_thue,
                'mo_ta'            => $request->mo_ta,
                'quy_mo'           => $request->quy_mo,
                'industry_id'      => $request->industry_id,
                'province_id'      => $request->province_id,
                'district_id'      => $request->district_id,
                'dia_chi_chi_tiet' => $request->dia_chi_chi_tiet,
                'email_lien_he'    => $request->email_lien_he,
                'hotline'          => $request->hotline,
                'website'          => $request->website,
                'trang_thai'       => 'cho_duyet',
            ]);

            Employer::create([
                'user_id'             => Auth::id(),
                'company_id'          => $company->id,
                'ho_ten'              => $request->ho_ten,
                'chuc_vu'             => $request->chuc_vu,
                'la_quan_tri_cong_ty' => 1,
            ]);

            DB::commit();
            return redirect()->route('employer.pending');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi lưu thông tin doanh nghiệp.')->withInput();
        }
    }


    public function showStep3Pending()
    {
        if (Auth::user()->employer->company->trang_thai === 'da_duyet') {
            return redirect()->route('employer.dashboard');
        }
        return view('auth.employer-step3');
    }

    //=============================================

    public function showLoginForm()
    {
        return view('auth.employer-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            
            $request->user()->update(['last_login_at' => now()]);

           
            if (Auth::user()->role === 'employer') {
                return redirect()->intended('/employer/dashboard');
            }

            
            Auth::logout();
            return back()->withErrors(['email' => 'Tài khoản của bạn không có quyền truy cập khu vực này.']);
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không chính xác.',
        ])->onlyInput('email');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/employer/login');
    }

    public function getDistricts($province_id)
    {
        $districts = District::where('province_id', $province_id)
                             ->orderBy('ten_quan_huyen', 'asc') 
                             ->get(['id', 'ten_quan_huyen']); 
                             
        return response()->json($districts);
    }
}
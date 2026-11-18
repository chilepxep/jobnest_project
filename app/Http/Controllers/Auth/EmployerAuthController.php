<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\District;
use App\Models\Employer;
use App\Models\Industry;
use App\Models\Province;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployerAuthController extends Controller
{
    public function showRegisterStep1()
    {
        $step1Data = Arr::except(session('employer_step1', []), ['password_hash']);
        return view('auth.employer-step1', compact('step1Data'));
    }

    public function processStep1(Request $request)
    {
        $data = $request->validate([
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
            'phone.unique'       => 'Số điện thoại này đã được sử dụng.',
        ]);

        session()->put('employer_step1', [
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
        ]);

        return redirect()->route('employer.register.step2');
    }

    public function showRegisterStep2()
    {
        if (Auth::check() && Auth::user()->employer) {
            return redirect()->route('employer.dashboard');
        }

        if (!session()->has('employer_step1')) {
            return redirect()->route('employer.register.step1')
                ->with('error', 'Vui lòng điền thông tin tài khoản trước.');
        }

        $industries = Industry::orderBy('ten_nganh', 'asc')->get();
        $provinces  = Province::orderBy('ten_tinh', 'asc')->get();
        $step2Data  = session('employer_step2', []);

        return view('auth.employer-step2', compact('industries', 'provinces', 'step2Data'));
    }

    public function processStep2(Request $request)
    {
        if (!session()->has('employer_step1')) {
            return redirect()->route('employer.register.step1');
        }

        $data = $request->validate([
            'ho_ten'           => 'required|string|max:150',
            'chuc_vu'          => 'required|string|max:100',

            'ten_cong_ty'      => 'required|string|max:255',
            'ma_so_thue'       => 'required|string|max:20|unique:companies,ma_so_thue',
            'mo_ta'            => 'required|string',
            'quy_mo'           => 'required|in:duoi_25,25_99,100_499,500_999,tren_1000',
            'industry_id'      => 'required|integer|exists:industries,id',
            'province_id'      => 'required|integer|exists:provinces,id',
            'district_id'      => ['nullable', 'integer',
                                   Rule::exists('districts', 'id')->where('province_id', $request->input('province_id'))],
            'dia_chi_chi_tiet' => 'required|string|max:255',
            'email_lien_he'    => 'required|email|max:150',
            'hotline'          => 'required|string|max:20',
            'website'          => 'nullable|url|max:255',
        ]);

        $step1 = session('employer_step1');

        
        $taken = User::where('email', $step1['email'])->exists()
            || (!empty($step1['phone']) && User::where('phone', $step1['phone'])->exists());

        if ($taken) {
            session()->forget(['employer_step1', 'employer_step2']);
            return redirect()->route('employer.register.step1')
                ->with('error', 'Email hoặc số điện thoại vừa được đăng ký bởi tài khoản khác. Vui lòng nhập lại.');
        }

        try {
            $user = DB::transaction(function () use ($data, $step1) {
                $user = User::create([
                    'email'         => $step1['email'],
                    'phone'         => $step1['phone'] ?? null,
                    'password_hash' => $step1['password_hash'],
                    'role'          => 'employer',
                    'status'        => 'active',
                ]);

                $company = Company::create([
                    'ten_cong_ty'      => $data['ten_cong_ty'],
                    'ma_so_thue'       => $data['ma_so_thue'],
                    'mo_ta'            => $data['mo_ta'],
                    'quy_mo'           => $data['quy_mo'],
                    'industry_id'      => $data['industry_id'],
                    'province_id'      => $data['province_id'],
                    'district_id'      => $data['district_id'] ?? null,
                    'dia_chi_chi_tiet' => $data['dia_chi_chi_tiet'],
                    'email_lien_he'    => $data['email_lien_he'],
                    'hotline'          => $data['hotline'],
                    'website'          => $data['website'] ?? null,
                    'trang_thai'       => 'cho_duyet',
                ]);

                Employer::create([
                    'user_id'             => $user->id,
                    'company_id'          => $company->id,
                    'ho_ten'              => $data['ho_ten'],
                    'chuc_vu'             => $data['chuc_vu'],
                    'la_quan_tri_cong_ty' => 1,
                ]);

                return $user;
            });
        } catch (QueryException $e) {
            session()->put('employer_step2', $request->except('_token'));

            // 1062 = trùng khoá duy nhất (email, SĐT, mã số thuế, slug) do 2 request chạy gần như cùng lúc
            if (($e->errorInfo[1] ?? null) === 1062) {
                return back()->with('error', 'Email, số điện thoại hoặc mã số thuế đã tồn tại. Vui lòng kiểm tra lại.');
            }

            report($e);
            return back()->with('error', 'Không thể hoàn tất đăng ký, vui lòng thử lại sau.');
        }

       
        session()->forget(['employer_step1', 'employer_step2']);

        Auth::login($user);
        $request->session()->regenerate(); 

    
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('verification.notice');
    }

    public function showStep3Pending()
    {
      
        $company = Auth::user()?->employer?->company;

        if (!$company) {
            return redirect('/employer/login')
                ->withErrors(['email' => 'Tài khoản của bạn chưa được liên kết với công ty nào.']);
        }

        if ($company->trang_thai === 'da_duyet') {
            return redirect()->route('employer.dashboard');
        }
        return view('auth.employer-step3', compact('company'));
    }

    //=============================================

    public function showLoginForm()
    {
        return view('auth.employer-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Vui lòng nhập email.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (Auth::attempt($credentials + ['role' => 'employer', 'status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->user()->update(['last_login_at' => now()]);

            return redirect()->intended('/employer/dashboard');
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không chính xác, hoặc tài khoản không có quyền truy cập khu vực này.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/employer/login');
    }

        public function changeEmail(Request $request)
    {
        // 1. Chặn bảo mật: Nếu đã xác thực rồi thì KHÔNG cho đổi ở đây nữa
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('employer.pending')
                            ->with('error', 'Tài khoản đã xác thực, không thể đổi email tại đây.');
        }

        // 2. Validate email mới
        $request->validate([
            'new_email' => 'required|email|unique:users,email|max:150',
        ], [
            'new_email.required' => 'Vui lòng nhập địa chỉ email mới.',
            'new_email.email'    => 'Định dạng email không hợp lệ.',
            'new_email.unique'   => 'Email này đã tồn tại trong hệ thống, vui lòng chọn email khác.',
        ]);

        // 3. Cập nhật DB
        $user = $request->user();
        $user->email = $request->new_email;
        $user->save();

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Đã cập nhật email thành công! Vui lòng kiểm tra hộp thư mới của bạn.');
    }

    public function getDistricts($province_id)
    {
        $districts = District::where('province_id', $province_id)
            ->orderBy('ten_quan_huyen', 'asc')
            ->get(['id', 'ten_quan_huyen']);

        return response()->json($districts);
    }


}
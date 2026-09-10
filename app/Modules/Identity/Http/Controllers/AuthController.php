<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            $companyId = $user->current_company_id ?? $user->companies()->first()?->id;
            if ($companyId) {
                session([
                    'active_company_id' => $companyId,
                    'current_company_id' => $companyId,
                ]);
            }

            return redirect()->intended(route('dashboard.index'));
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($request) {
            // 1. Create Company
            $company = Company::create([
                'name' => $request->company_name,
                'legal_name' => $request->company_name,
                'entity_type' => 'PT',
                'status' => 'active',
                'fiscal_year_start_month' => 1,
                'currency_code' => 'IDR',
                'timezone' => 'Asia/Jakarta',
            ]);

            // 2. Create Default Branch
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => 'Kantor Pusat',
                'code' => 'HO-01',
                'is_head_office' => true,
                'is_active' => true,
            ]);

            // 3. Create User
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'current_company_id' => $company->id,
                'current_branch_id' => $branch->id,
            ]);

            // 4. Assign Owner Role
            $ownerRole = DB::table('roles')->where('slug', 'owner')->first();
            DB::table('company_users')->insert([
                'company_id' => $company->id,
                'user_id' => $newUser->id,
                'role_id' => $ownerRole?->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. Create Free/Trial Subscription
            $plan = DB::table('plans')->first();
            if ($plan) {
                DB::table('subscriptions')->insert([
                    'company_id' => $company->id,
                    'plan_id' => $plan->id,
                    'status' => 'trialing',
                    'trial_ends_at' => now()->addDays(14),
                    'starts_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $newUser;
        });

        Auth::login($user);
        $request->session()->regenerate();
        session([
            'active_company_id' => $user->current_company_id,
            'current_company_id' => $user->current_company_id,
        ]);

        return redirect()->route('onboarding.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

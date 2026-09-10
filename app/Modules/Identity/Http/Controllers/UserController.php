<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Models\Branch;
use App\Modules\Identity\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $this->ensureDefaultRolesExist($companyId);

        $members = DB::table('company_users')
            ->join('users', 'company_users.user_id', '=', 'users.id')
            ->leftJoin('roles', 'company_users.role_id', '=', 'roles.id')
            ->where('company_users.company_id', $companyId)
            ->select(
                'users.id as user_id',
                'users.name',
                'users.email',
                'roles.name as role_name',
                'company_users.is_active',
                'company_users.created_at'
            )
            ->get();

        $roles = Role::where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        })->get();

        $branches = Branch::where('company_id', $companyId)->get();

        return view('identity.users.index', compact('members', 'roles', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'password' => 'nullable|string|min:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? 'password123'),
                'current_company_id' => $companyId,
            ]);
        }

        // Check if already in this company
        $exists = DB::table('company_users')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            DB::table('company_users')
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->update([
                    'role_id' => $validated['role_id'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('company_users')->insert([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'role_id' => $validated['role_id'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', "Anggota tim {$user->name} berhasil ditambahkan ke entitas.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akses akun Anda sendiri.');
        }

        DB::table('company_users')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->route('users.index')
            ->with('success', "Akses untuk {$user->name} berhasil dicabut.");
    }

    private function ensureDefaultRolesExist(int $companyId): void
    {
        if (Role::where('company_id', $companyId)->orWhereNull('company_id')->count() === 0) {
            $defaultRoles = [
                ['name' => 'Owner', 'slug' => 'owner', 'description' => 'Pemilik & Pengendali Penuh Entitas Bisnis'],
                ['name' => 'Finance & Accounting Manager', 'slug' => 'finance-manager', 'description' => 'Persetujuan transaksi dan posting jurnal'],
                ['name' => 'Staff Accountant', 'slug' => 'accountant', 'description' => 'Input dokumen faktur dan pencatatan buku kas'],
                ['name' => 'Tax Specialist', 'slug' => 'tax-specialist', 'description' => 'Pengelolaan SPT Masa PPN, PPh, dan Coretax'],
                ['name' => 'Auditor Eksternal', 'slug' => 'auditor', 'description' => 'Hak akses baca (Read-only) untuk pemeriksaan laporan'],
            ];

            foreach ($defaultRoles as $r) {
                Role::create([
                    'company_id' => $companyId,
                    'name' => $r['name'],
                    'slug' => $r['slug'],
                    'description' => $r['description'],
                    'is_system' => true,
                ]);
            }
        }
    }
}

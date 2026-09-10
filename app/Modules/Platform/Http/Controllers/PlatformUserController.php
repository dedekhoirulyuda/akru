<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PlatformUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['companies' => function ($q) {
            $q->withPivot('role_id', 'is_active');
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('superadmin') && $request->superadmin !== '') {
            $query->where('is_superadmin', (bool) $request->superadmin);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('platform.users.index', compact('users'));
    }

    public function toggleSuperAdmin(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id() && $user->is_superadmin) {
            return back()->with('error', 'Anda tidak dapat mencabut hak Super Admin dari akun Anda sendiri.');
        }

        $user->update([
            'is_superadmin' => !$user->is_superadmin,
        ]);

        $status = $user->is_superadmin ? 'dijadikan Super Admin Platform' : 'dicabut dari status Super Admin';

        return back()->with('success', "Pengguna {$user->name} berhasil {$status}.");
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', "Password pengguna {$user->name} berhasil direset.");
    }

    public function impersonate(int $id): RedirectResponse
    {
        $targetUser = User::findOrFail($id);
        $originalSuperAdminId = Auth::id();
        $originalSuperAdminName = Auth::user()->name;

        if ($targetUser->id === $originalSuperAdminId) {
            return back()->with('error', 'Anda sudah login sebagai akun ini.');
        }

        // Save original super admin state in session
        session([
            'impersonated_by' => $originalSuperAdminId,
            'impersonator_name' => $originalSuperAdminName,
        ]);

        // Login as target user
        Auth::login($targetUser);

        // Set active company to first company of user if available
        $company = $targetUser->companies()->first();
        if ($company) {
            session(['active_company_id' => $company->id, 'current_company_id' => $company->id]);
        }

        return redirect()->route('dashboard.index')
            ->with('info', "Anda sekarang masuk sebagai '{$targetUser->name}' (Mode Bantuan / Impersonasi).");
    }

    public function stopImpersonate(): RedirectResponse
    {
        if (!session()->has('impersonated_by')) {
            return redirect()->route('dashboard.index');
        }

        $originalAdminId = session('impersonated_by');
        session()->forget(['impersonated_by', 'impersonator_name']);

        $superAdmin = User::findOrFail($originalAdminId);
        Auth::login($superAdmin);

        return redirect()->route('platform.users.index')
            ->with('success', 'Sesi impersonasi dihentikan. Anda kembali sebagai Super Admin Platform.');
    }
}

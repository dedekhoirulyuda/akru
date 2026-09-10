<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * OnboardingController — guides new organizations through initial configuration.
 *
 * Blueprint §2.1: Onboarding wizard (company profile, tax settings, COA template).
 */
class OnboardingController extends Controller
{
    /**
     * Show the onboarding wizard index.
     */
    public function index(Request $request)
    {
        return view('onboarding.index');
    }

    /**
     * Complete onboarding and unlock operational transactions.
     */
    public function complete(Request $request)
    {
        return redirect()->route('dashboard.index')
            ->with('success', 'Selamat! Pengaturan awal perusahaan Anda telah berhasil diselesaikan. Selamat datang di AKRU.');
    }
}

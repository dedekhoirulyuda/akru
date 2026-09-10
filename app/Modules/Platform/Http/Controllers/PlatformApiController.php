<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Platform\Models\PlatformApiToken;
use App\Modules\Platform\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformApiController extends Controller
{
    public function index(): View
    {
        // Load all platform settings keyed by setting key
        $settings = PlatformSetting::all()->keyBy('key');

        // Load active developer tokens
        $tokens = PlatformApiToken::with('creator')->latest()->get();

        return view('platform.api.index', compact('settings', 'tokens'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $group = $request->input('group', 'general');

        $configs = match($group) {
            'ai' => [
                'ai_provider' => ['type' => 'string', 'secret' => false],
                'ai_gemini_api_key' => ['type' => 'string', 'secret' => true],
                'ai_openai_api_key' => ['type' => 'string', 'secret' => true],
                'ai_default_model' => ['type' => 'string', 'secret' => false],
                'ai_temperature' => ['type' => 'string', 'secret' => false],
            ],
            'payment' => [
                'payment_gateway' => ['type' => 'string', 'secret' => false],
                'midtrans_server_key' => ['type' => 'string', 'secret' => true],
                'midtrans_client_key' => ['type' => 'string', 'secret' => false],
                'midtrans_environment' => ['type' => 'string', 'secret' => false],
                'xendit_secret_key' => ['type' => 'string', 'secret' => true],
                'xendit_webhook_token' => ['type' => 'string', 'secret' => true],
            ],
            'coretax' => [
                'coretax_environment' => ['type' => 'string', 'secret' => false],
                'coretax_api_endpoint' => ['type' => 'string', 'secret' => false],
                'coretax_client_id' => ['type' => 'string', 'secret' => false],
                'coretax_client_secret' => ['type' => 'string', 'secret' => true],
            ],
            'notification' => [
                'wa_gateway_provider' => ['type' => 'string', 'secret' => false],
                'wa_api_token' => ['type' => 'string', 'secret' => true],
                'wa_sender_number' => ['type' => 'string', 'secret' => false],
                'mail_host' => ['type' => 'string', 'secret' => false],
                'mail_port' => ['type' => 'string', 'secret' => false],
                'mail_username' => ['type' => 'string', 'secret' => false],
                'mail_password' => ['type' => 'string', 'secret' => true],
                'mail_from_address' => ['type' => 'string', 'secret' => false],
            ],
            default => []
        };

        foreach ($configs as $key => $meta) {
            if ($request->has($key)) {
                $val = $request->input($key);
                // Don't overwrite secret with masked dots
                if ($meta['secret'] && str_contains($val, '••••••••')) {
                    continue;
                }

                PlatformSetting::set(
                    key: $key,
                    value: $val,
                    group: $group,
                    isSecret: $meta['secret'],
                    description: "Pengaturan {$key}"
                );
            }
        }

        $groupLabel = match($group) {
            'ai' => 'AI Provider & Model',
            'payment' => 'Payment Gateway',
            'coretax' => 'Coretax DJP',
            'notification' => 'Notifikasi WhatsApp & Email',
            default => 'Integrasi API'
        };

        return back()->with('success', "Konfigurasi {$groupLabel} berhasil disimpan.");
    }

    public function generateToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'abilities' => 'nullable|array',
            'expires_days' => 'nullable|integer|min:1|max:365',
        ]);

        $rawToken = 'akru_live_' . Str::random(40);
        $expiresAt = $request->filled('expires_days') ? now()->addDays((int) $request->expires_days) : null;

        PlatformApiToken::create([
            'name' => $validated['name'],
            'token' => hash('sha256', $rawToken),
            'abilities' => $validated['abilities'] ?? ['read', 'write'],
            'expires_at' => $expiresAt,
            'created_by' => Auth::id(),
        ]);

        return back()
            ->with('new_token_plain', $rawToken)
            ->with('new_token_name', $validated['name'])
            ->with('success', "API Token '{$validated['name']}' berhasil dibuat. Simpan token ini sekarang karena tidak akan ditampilkan lagi.");
    }

    public function revokeToken(int $id): RedirectResponse
    {
        $token = PlatformApiToken::findOrFail($id);
        $name = $token->name;
        $token->delete();

        return back()->with('success', "API Token '{$name}' berhasil dicabut.");
    }
}

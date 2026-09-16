<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Services\Contracts\AiProviderContract;
use App\Modules\Ai\Services\DTOs\AiRequest;
use App\Modules\Ai\Services\DTOs\AiResponse;
use App\Modules\Ai\Services\Providers\AkruNativeProvider;
use App\Modules\Ai\Services\Providers\GeminiProvider;
use App\Modules\Ai\Services\Providers\OpenAiProvider;
use App\Modules\Ai\Models\AiCompanyProviderSetting;
use App\Modules\Ai\Models\AiProvider;
use App\Modules\Core\Models\Company;
use App\Modules\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;

class AiProviderRouter
{
    public function __construct(
        protected AkruNativeProvider $nativeProvider,
        protected GeminiProvider $geminiProvider,
        protected OpenAiProvider $openAiProvider
    ) {}

    /**
     * Resolve the appropriate provider and execute chat with graceful fallback.
     */
    public function route(AiRequest $request): AiResponse
    {
        $companyId = $request->companyId;
        $company = Company::find($companyId);
        $isTrial = $company ? $company->isTrial() : false;

        // Check if company has custom provider preference in session/setting
        $pref = $request->modelCode ?? session('ai_provider_preference');
        $setting = AiCompanyProviderSetting::with('provider', 'defaultModel')
            ->where('company_id', $companyId)
            ->where('is_enabled', true)
            ->first();

        // 1. Determine target provider
        $targetProvider = 'akru_native';

        if (!$isTrial) {
            if ($setting && $setting->provider) {
                $targetProvider = $setting->provider->code;
            } else {
                $defaultGlobal = PlatformSetting::get('ai_provider') ?: env('AI_DEFAULT_PROVIDER');
                if ($defaultGlobal === 'gemini' || $defaultGlobal === 'google_gemini') {
                    $targetProvider = 'google_gemini';
                } elseif ($defaultGlobal === 'openai') {
                    $targetProvider = 'openai';
                }
            }
        }

        // If user explicitly requested native
        if ($pref === 'akru_native') {
            $targetProvider = 'akru_native';
        }

        // 2. Execute with Fallback Loop
        if ($targetProvider === 'google_gemini') {
            try {
                return $this->geminiProvider->chat($request);
            } catch (\Throwable $e) {
                Log::warning("GeminiProvider failed: {$e->getMessage()}. Attempting fallback.");

                // Try OpenAI fallback if available
                if (PlatformSetting::get('ai_openai_api_key') || env('OPENAI_API_KEY')) {
                    try {
                        $res = $this->openAiProvider->chat($request);
                        $res->providerDisclosure['fallback_used'] = true;
                        return $res;
                    } catch (\Throwable $e2) {
                        Log::warning("OpenAI fallback also failed: {$e2->getMessage()}.");
                    }
                }

                // Final fallback: AKRU Native
                $res = $this->nativeProvider->chat($request);
                $res->providerDisclosure['fallback_used'] = true;
                return $res;
            }
        }

        if ($targetProvider === 'openai') {
            try {
                return $this->openAiProvider->chat($request);
            } catch (\Throwable $e) {
                Log::warning("OpenAiProvider failed: {$e->getMessage()}. Attempting fallback.");

                if (PlatformSetting::get('ai_gemini_api_key') || env('GEMINI_API_KEY')) {
                    try {
                        $res = $this->geminiProvider->chat($request);
                        $res->providerDisclosure['fallback_used'] = true;
                        return $res;
                    } catch (\Throwable $e2) {
                        Log::warning("Gemini fallback also failed: {$e2->getMessage()}.");
                    }
                }

                $res = $this->nativeProvider->chat($request);
                $res->providerDisclosure['fallback_used'] = true;
                return $res;
            }
        }

        // Default: AKRU Native
        return $this->nativeProvider->chat($request);
    }

    /**
     * Get instance of a specific provider by code.
     */
    public function getProvider(string $code): AiProviderContract
    {
        return match ($code) {
            'google_gemini', 'gemini' => $this->geminiProvider,
            'openai' => $this->openAiProvider,
            default => $this->nativeProvider,
        };
    }
}

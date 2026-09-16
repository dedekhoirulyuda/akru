<?php

namespace App\Modules\Ai\Services\Contracts;

use App\Modules\Ai\Services\DTOs\AiRequest;
use App\Modules\Ai\Services\DTOs\AiResponse;
use App\Modules\Ai\Services\DTOs\ProviderCapabilities;
use App\Modules\Ai\Services\DTOs\ProviderHealth;

interface AiProviderContract
{
    /**
     * Send normalized chat request and receive normalized response.
     */
    public function chat(AiRequest $request): AiResponse;

    /**
     * Return provider capabilities.
     */
    public function capabilities(): ProviderCapabilities;

    /**
     * Check provider health/connectivity.
     */
    public function healthCheck(?string $apiKey = null): ProviderHealth;
}

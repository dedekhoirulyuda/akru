<?php

namespace App\Modules\Ai\Services\DTOs;

class ProviderHealth
{
    public function __construct(
        public bool $isHealthy,
        public string $status,
        public ?string $message = null,
        public int $latencyMs = 0
    ) {}
}

<?php

namespace App\Modules\Ai\Services\DTOs;

class AiRequest
{
    public function __construct(
        public int $companyId,
        public int $userId,
        public string $userMessage,
        public string $mode = 'general',
        public ?int $conversationId = null,
        public array $branchScope = [],
        public array $periodScope = [],
        public array $allowedTools = [],
        public ?string $requiredOutputSchema = null,
        public string $locale = 'id',
        public string $timezone = 'Asia/Jakarta',
        public ?string $apiKey = null,
        public ?string $modelCode = null,
        public array $conversationHistory = [],
        public ?string $idempotencyKey = null
    ) {}
}

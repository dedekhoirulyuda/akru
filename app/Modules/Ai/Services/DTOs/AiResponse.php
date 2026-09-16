<?php

namespace App\Modules\Ai\Services\DTOs;

class AiResponse
{
    public function __construct(
        public string $answer,
        public string $summary,
        public string $intent,
        public array $scope,
        public array $metrics = [],
        public array $findings = [],
        public array $recommendations = [],
        public array $citations = [],
        public array $assumptions = [],
        public array $missingData = [],
        public float $confidence = 0.95,
        public string $riskLevel = 'low',
        public bool $requiresHumanReview = true,
        public array $providerDisclosure = [],
        public array $toolCalls = [],
        public array $usage = [],
        public string $status = 'success',
        public ?string $errorMessage = null
    ) {}

    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'summary' => $this->summary,
            'intent' => $this->intent,
            'scope' => $this->scope,
            'metrics' => $this->metrics,
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
            'citations' => $this->citations,
            'assumptions' => $this->assumptions,
            'missing_data' => $this->missingData,
            'confidence' => $this->confidence,
            'risk_level' => $this->riskLevel,
            'requires_human_review' => $this->requiresHumanReview,
            'provider_disclosure' => $this->providerDisclosure,
            'tool_calls' => $this->toolCalls,
            'usage' => $this->usage,
            'status' => $this->status,
            'error_message' => $this->errorMessage,
        ];
    }
}

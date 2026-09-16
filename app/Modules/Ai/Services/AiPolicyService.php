<?php

namespace App\Modules\Ai\Services;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\AiChatUsage;
use App\Modules\Ai\Models\AiRedactionEvent;
use Illuminate\Support\Str;

class AiPolicyService
{
    /**
     * Verify if the company has remaining chat quota for the day.
     */
    public function checkQuota(int $companyId): array
    {
        $company = Company::find($companyId);
        $chatLimit = $company ? $company->getAiChatLimit() : null;
        $todayUsage = AiChatUsage::getTodayUsage($companyId);

        $hasQuota = ($chatLimit === null) || ($todayUsage < $chatLimit);

        return [
            'allowed' => $hasQuota,
            'limit' => $chatLimit,
            'used' => $todayUsage,
            'remaining' => $chatLimit !== null ? max(0, $chatLimit - $todayUsage) : null,
            'is_unlimited' => ($chatLimit === null),
            'plan_name' => $company?->subscription?->plan?->name ?? 'Free Trial',
        ];
    }

    /**
     * Redact sensitive personal or restricted information before sending out.
     */
    public function redactSensitiveData(int $companyId, string $text): string
    {
        $redacted = $text;

        // 1. Mask 16-digit credit card patterns
        $cardPattern = '/\b(?:\d{4}[-\s]?){3}\d{4}\b/';
        if (preg_match($cardPattern, $redacted)) {
            $redacted = preg_replace($cardPattern, '[REDACTED_CARD_NUMBER]', $redacted);
            AiRedactionEvent::create([
                'company_id' => $companyId,
                'request_id' => (string) Str::uuid(),
                'rule_code' => 'PCI_CARD_REDACTION',
                'field_type' => 'card_number',
                'action' => 'mask',
                'count' => 1,
            ]);
        }

        return $redacted;
    }

    /**
     * Ensure human-in-the-loop: block any autonomous execution attempts.
     */
    public function enforceHumanReview(array $proposedAction): bool
    {
        $restrictedActions = ['post_journal', 'approve_document', 'pay_invoice', 'file_tax', 'close_period'];
        if (in_array($proposedAction['type'] ?? '', $restrictedActions)) {
            return false; // Direct autonomous posting strictly disallowed!
        }
        return true;
    }
}

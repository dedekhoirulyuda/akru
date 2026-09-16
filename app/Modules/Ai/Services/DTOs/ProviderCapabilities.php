<?php

namespace App\Modules\Ai\Services\DTOs;

class ProviderCapabilities
{
    public function __construct(
        public bool $supportsTools = true,
        public bool $supportsStreaming = false,
        public bool $supportsMultimodal = false,
        public bool $isFree = false,
        public int $contextLimit = 8192,
        public int $outputLimit = 4096
    ) {}
}

<?php

namespace CampaignMonitor\DTOs;

use Illuminate\Support\Str;

class CampaignMonitorTemplate
{
    public function __construct(
        public string $templateName,
        public string $smartEmailId,
    ) {

        if (Str::contains($this->templateName, ['.', '_'])) {
            $this->templateName = Str::replace(['.', '_'], '-', $this->templateName);
        }
    }
}

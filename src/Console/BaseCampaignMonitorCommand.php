<?php

namespace CampaignMonitor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

abstract class BaseCampaignMonitorCommand extends Command
{
    protected ?string $apiKey;

    abstract public function handle();

    public function __construct()
    {
        parent::__construct();

        $this->apiKey = config('campaign-monitor.config.apiKey');
    }

    protected function getTransactionalEmail(string $smartEmailId): array
    {
        if (! $this->apiKey) {
            $this->error('No API Key found');
            throw new \Exception('No api key found');
        }

        $response = Http::withBasicAuth($this->apiKey, 'x')
            ->timeout(15)
            ->retry(3, 250)
            ->acceptJson()
            ->get("https://createsend.com/api/v3.3/transactional/smartemail/{$smartEmailId}.json");

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch template');
        }

        return $response->json();
    }
}

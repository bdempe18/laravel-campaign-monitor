<?php

namespace Tests;

use CampaignMonitor\CampaignMonitorServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [CampaignMonitorServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Sensible defaults for tests; individual tests may override
        $app['config']->set('campaign-monitor.config.apiKey', 'test-api-key');
        $app['config']->set('campaign-monitor.templates', []);
    }
}

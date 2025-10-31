<?php

namespace CampaignMonitor;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\App;
use CampaignMonitor\Transport\CampaignMonitorTransport;

class CampaignMonitorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/campaign-monitor.php' => config_path('campaign-monitor.php'),
        ], 'config');

        Mail::extend('campaign-monitor', function () {
            $config = config('campaign-monitor');

            if (App::isProduction()) {
                return new CampaignMonitorTransport(
                    $config['api_key'],
                    $config['from']['address'],
                    $config['from']['name']
                );
            }

            return Mail::mailer('smtp')->getSymfonyTransport();
        });

            if ($this->app->runningInConsole()) {
        $this->commands([
            \CampaignMonitor\Console\ShowTemplate::class,
            \CampaignMonitor\Console\ListTemplates::class,
            \CampaignMonitor\Console\SyncTemplates::class,

        ]);
    }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/campaign-monitor.php',
            'campaign-monitor'
        );
    }
}

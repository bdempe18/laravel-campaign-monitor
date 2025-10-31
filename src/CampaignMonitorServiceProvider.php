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
        ], 'campaign-monitor');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'campaign-monitor');

        Mail::extend('campaign-monitor', function () {
            if (App::isProduction()) {
                return new CampaignMonitorTransport;
            }

            return Mail::mailer('smtp')->getSymfonyTransport();
        });

            if ($this->app->runningInConsole()) {
                $this->commands([
                    \CampaignMonitor\Console\Commands\ShowTemplate::class,
                    \CampaignMonitor\Console\Commands\ListTemplates::class,
                    \CampaignMonitor\Console\Commands\SyncTemplates::class,

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

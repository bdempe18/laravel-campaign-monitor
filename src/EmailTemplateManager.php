<?php

namespace CampaignMonitor;

use CampaignMonitor\Exceptions\CampaignMonitorException;
use CampaignMonitor\DTOs\CampaignMonitorTemplate;
use Illuminate\Support\Str;

class EmailTemplateManager
{
    public const DEFAULT_TEMPLATE_NAME = 'default';

    /**
     * Get a template ID by category and name
     *
     * Path should be in dot notation
     */
    public static function get(string $path): CampaignMonitorTemplate
    {
        $configPath = "campaign-monitor.templates.$path";
        $template = config($configPath);

        if (is_null($template)) {
            throw new CampaignMonitorException($path);
        }

        return new CampaignMonitorTemplate($path, $template);
    }

    public static function findTemplateName(string $smartEmailId): string
    {
        $templates = config('campaign-monitor.templates');

        if (blank($templates)) {
            return self::DEFAULT_TEMPLATE_NAME;
        }

        foreach ($templates as $group => $items) {
            foreach ($items as $name => $id) {
                if ($id === $smartEmailId) {
                    return Str::slug("{$group}__{$name}");
                }
            }
        }

        return self::DEFAULT_TEMPLATE_NAME;
    }
}

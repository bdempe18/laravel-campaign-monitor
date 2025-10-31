<?php

namespace CampaignMonitor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ListTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign-monitor:list {categories?* : Filter by category}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all email templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templates = config('campaign-monitor.templates');

        if (empty($templates)) {
            $this->error('No templates found in campaign-monitor configuration.');

            return 1;
        }

        $filterCategories = $this->argument('categories') ?: [];

        $allTemplates = [];
        foreach ($templates as $category => $categoryTemplates) {
            // Skip categories that don't match the filter
            if (! empty($filterCategories) && ! in_array($category, $filterCategories)) {
                continue;
            }

            $result = [];
            $this->flattenTemplates(Arr::wrap($categoryTemplates), $category, $result);
            $allTemplates = array_merge($allTemplates, $result);
        }

        if (empty($allTemplates)) {
            if (! empty($filterCategories)) {
                $this->warn('No templates found for categories: '.implode(', ', $filterCategories));
                $this->line('Available categories: '.implode(', ', array_keys($templates)));
            } else {
                $this->warn('No templates found.');
            }

            return 0;
        }

        // Calculate the maximum name length for consistent formatting
        $maxNameLength = max(array_map('strlen', array_column($allTemplates, 'Name')));
        $targetWidth = max(60, $maxNameLength + 20); // Minimum 60 chars, or name length + 20

        foreach ($allTemplates as $template) {
            $dots = str_repeat('.', max(1, $targetWidth - strlen($template['Name'])));
            $this->line(sprintf(
                '<fg=green>%s</> %s %s</>',
                $template['Name'],
                $dots,
                $template['Template ID']
            ));
        }

    }

    /**
     * Recursively flatten nested template arrays
     */
    private function flattenTemplates(array $templates, string $path = '', array &$result = []): array
    {
        foreach ($templates as $key => $value) {
            if (empty($key)) {
                $currentPath = $path;
            } else {
                $currentPath = $path ? "{$path}.{$key}" : $key;
            }

            if (is_array($value)) {
                // Recursively process nested arrays
                $this->flattenTemplates($value, $currentPath, $result);
            } else {
                // This is a template ID
                $result[] = [
                    'Name' => $currentPath,
                    'Template ID' => $value,
                ];
            }
        }

        return $result;
    }
}

<?php

namespace CampaignMonitor\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncTemplates extends BaseCampaignMonitorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign-monitor:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Campaign Monitor Smart Email templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('campaign-monitor.config.apiKey');
        $templates = config('campaign-monitor.templates');
        $cache = resource_path('views/emails/campaign-monitor');

        if (! $apiKey) {
            $this->error('Campaign Monitor API key not found');

            return Command::FAILURE;
        }

        // Create the cache directory if it doesn't exist
        if (! is_dir($cache)) {
            mkdir($cache, 0755, true);
            $this->info("Created template directory: {$cache}");
        }

        // Check for existing files and ask for confirmation
        $existingFiles = [];
        $newFiles = [];

        foreach ($templates as $group => $items) {
            foreach ($items as $name => $smartEmailId) {
                $filename = Str::slug("{$group}__{$name}").'.blade.php';
                $path = "{$cache}/{$filename}";

                if (file_exists($path)) {
                    $existingFiles[] = $filename;
                } else {
                    $newFiles[] = $filename;
                }
            }
        }

        if (! empty($existingFiles)) {
            $this->warn('The following existing files will be overwritten:');
            foreach ($existingFiles as $file) {
                $this->line("  - {$file}");
            }

            if (! $this->confirm('Do you want to continue? This will overwrite existing files.')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        if (! empty($newFiles)) {
            $this->info('The following new files will be created:');
            foreach ($newFiles as $file) {
                $this->line("  - {$file}");
            }
        }

        $this->newLine();
        $this->info('Fetching templates from Campaign Monitor...');

        foreach ($templates as $group => $items) {
            foreach ($this->flatten($items) as $name => $smartEmailId) {
                $filename = Str::slug("{$group}__{$name}").'.blade.php';
                $path = "{$cache}/{$filename}";

                try {
                    $detail = $this->getTransactionalEmail($smartEmailId);
                } catch (\Exception $e) {
                    $this->error("Failed to fetch template `{$name}`");
                    continue;
                }

                $properties = $detail['Properties'] ?? null;

                if (! is_array($properties)) {
                    $this->error("Unexpected payload for {$name}");

                    continue;
                }

                $content = $properties['Content'];
                $preview = $properties['HtmlPreviewUrl'];

                $html = $content['HTML'] ?? null;

                if (! $html || $html === 'Content managed in Email Builder') {
                    $this->warn("  HTML template not found for {$name}, generating mock payload template");
                    $html = $this->generateBladeTemplate($name, $content['EmailVariables'] ?? [], $preview);
                }

                file_put_contents($path, $html);
            }
        }

        $this->newLine();
        $this->info('Finished syncing Campaign Monitor templates');
        $this->comment('Templates are now available at: '.$cache);
    }

    protected function flatten(array $arr, string $prefix = ''): array
    {
        $result = [];

        foreach ($arr as $key => $value) {
            $newKey = $prefix ? "{$prefix}_{$key}" : $key;

            if (is_array($value)) {
                $result += $this->flatten($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    public function generateBladeTemplate(string $templateName, array $variables = [], string $previewLink = ''): string
    {
        $preview = 'Campaign Monitor';

        if (! blank($previewLink)) {
            $preview = '<a href="'.$previewLink.'" target="_blank">'.$preview.'</a>';
        }

        $varsPhp = '';
        $rows = '';

        foreach ($variables as $rawVar) {

            $var = preg_replace('/[^A-Za-z0-9_]/', '_', $rawVar);
            $var = preg_replace('/^[0-9]+/', '', $var);

            $varsPhp .= "\${$var} = \${$var} ?? 'N/A' ;\n";
            $rows .= <<<HTML
                <tr>
                    <td style="font-weight:bold; padding:6px 8px; border-bottom:1px solid #ddd; background:#f9f9f9;">{$var}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #ddd;">{{ \${$var} }}</td>
                </tr>
            HTML;
        }

        return <<<BLADE
        @php
        {$varsPhp}
        @endphp

        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8">
                <title>{$templateName}</title>
            </head>
            <body>
                <p>This is a preview of the template: {$templateName}</p>
                <p>To see the actual template, please visit {$preview}.</p>

                <p><strong>Payload preview:</strong></p>
                <table cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse; width:100%; max-width:600px;">
                    {$rows}
                </table>
            </body>
        </html>
        BLADE;
    }
}

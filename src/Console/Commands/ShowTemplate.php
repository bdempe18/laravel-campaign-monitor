<?php

namespace CampaignMonitor\Console\Commands;

use CampaignMonitor\EmailTemplateManager;
use Illuminate\Console\Command;

class ShowTemplate extends BaseCampaignMonitorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign-monitor:show
                            { template : Name of template to show details for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templateName = $this->argument('template');
        try {
            $template = EmailTemplateManager::get($templateName);
        } catch (\Exception $e) {
            $this->error("No template found with name `$templateName`");

            return Command::FAILURE;
        }

        $detail = $this->getTransactionalEmail($template->smartEmailId);

        $emp = fn ($text) => "<fg=green;options=bold>$text</>";

        $this->line($emp('Campaign Monitor Email Details'));
        $this->components->twoColumnDetail('Name', $detail['Name']);
        $this->components->twoColumnDetail('Smart Email ID', $detail['SmartEmailID']);
        $this->components->twoColumnDetail('Status', $detail['Status']);

        $this->newLine();

        $props = $detail['Properties'];
        $this->line($emp('Email Header'));
        $this->components->twoColumnDetail('Subject', $props['Subject']);
        $this->components->twoColumnDetail('From', $props['From']);
        $this->components->twoColumnDetail('ReplyTo', $props['From']);

        $this->newLine();

        $this->line($emp('Variables'));
        $variables = data_get($props, 'Content.EmailVariables', []);

        if ($variables) {
            foreach ($variables as $var) {
                $this->components->twoColumnDetail($var);
            }
        } else {
            $this->line('<fg=gray>No variables required for email</>');
        }

        $this->newLine();

        $this->line('Preview the transactional email below');
        $url = $props['HtmlPreviewUrl'];
        $this->line("<href=$url;fg=gray>$url</>");
    }
}

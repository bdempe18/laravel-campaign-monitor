<?php

namespace CampaignMonitor;

use CampaignMonitor\DTOs\CampaignMonitorTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignMonitor extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    protected CampaignMonitorTemplate $template;

    protected array $data;

    public function __construct(CampaignMonitorTemplate $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function build()
    {
        return $this->view($this->getView())
            ->with('data', $this->data)
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()
                    ->addTextHeader(
                        'X-Campaign-Monitor-Smart-Email-ID',
                        $this->getSmartEmailId()
                    )
                    ->addTextHeader(
                        'X-Campaign-Monitor-Data',
                        json_encode($this->data)
                    );
            });
    }

    protected function getView(): string
    {
        $prefix = 'emails.campaign-monitor.';
        $viewString = $prefix.$this->getTemplateName();

        if (! view()->exists($viewString())) {
            $viewString = 'campaign-monitor::emails.campaign-monitor.default';
        }

        return $viewString;
    }

    public function getSmartEmailId(): string
    {
        return $this->template->smartEmailId;
    }

    public function getTemplateName(): string
    {
        return $this->template->templateName;
    }

    public function getData(): array
    {
        return $this->data;
    }
}

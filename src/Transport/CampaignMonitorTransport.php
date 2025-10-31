<?php

namespace CampaignMonitor\Transport;

use CampaignMonitor\Exceptions\CampaignMonitorException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

class CampaignMonitorTransport extends AbstractTransport
{
    protected string $apiKey;

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = config('campaign-monitor.config.apiKey', '');

        if (blank($this->apiKey)) {
            throw new CampaignMonitorException(null, 'No API key found');
        }
    }

    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();

        // Ensure we have a Message object for MessageConverter
        if (! $originalMessage instanceof Message) {
            throw new CampaignMonitorException('campaign-monitor-transport', 'Unsupported message type: '.get_class($originalMessage));
        }

        try {
            $email = MessageConverter::toEmail($originalMessage);
        } catch (\Exception $e) {
            throw new CampaignMonitorException('campaign-monitor-transport', 'Unable to convert message to email: '.$e->getMessage());
        }

        $smartEmailId = $this->extractSmartEmailId($email);
        $data = $this->extractData($email);

        if (! $smartEmailId) {
            throw new CampaignMonitorException('campaign-monitor-transport', 'Smart email ID not found in message headers');
        }

        $payload = $this->formatPayload($email, $data);
        $this->cmPost($payload, $smartEmailId);
    }

    private function formatPayload(Email $email, array $data): array
    {
        return [
            'To' => $this->extractRecipients($email->getTo()),
            'Cc' => $this->extractRecipients($email->getCc()),
            'Bcc' => $this->extractRecipients($email->getBcc()),
            'Data' => $data,
        ];
    }

    private function extractRecipients($addresses): array
    {
        if (! $addresses) {
            return [];
        }

        return array_map(fn ($address) => $address->toString(), $addresses);
    }

    private function extractSmartEmailId(Email $email): ?string
    {
        $header = $email->getHeaders()->get('X-Campaign-Monitor-Smart-Email-ID');

        return $header ? $header->getBodyAsString() : null;
    }

    private function extractData(Email $email): array
    {
        $header = $email->getHeaders()->get('X-Campaign-Monitor-Data');
        if ($header) {
            return json_decode($header->getBodyAsString(), true) ?? [];
        }

        return [];
    }

    protected function cmPost(array $payload, string $smartEmailId): void
    {
        try {
            $cm = new \CS_REST_Transactional_SmartEmail(
                $smartEmailId,
                ['api_key' => $this->apiKey]
            );

            $trackConsent = $payload['Track'] ?? 'Yes';
            $result = $cm->send($payload, $trackConsent);

            /** @phpstan-ignore-next-line */
            if (! $result->was_successful()) {
                /** @phpstan-ignore-next-line */
                throw new CampaignMonitorException('campaign-monitor-api', 'Campaign Monitor API error: '.json_encode($result->response));
            }
        } catch (\Exception $e) {
            Log::error('Campaign Monitor transport error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    public function __toString(): string
    {
        return 'campaignmonitor-smartemail';
    }
}

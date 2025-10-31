<?php

namespace CampaignMonitor\Exceptions;

use Exception;

class CampaignMonitorException extends Exception
{
    protected $email_name;

    public function __construct(?string $email_name, ?string $message = null)
    {
        $this->email_name = $email_name;

        if (is_null($message)) {
            $message = "Couldn't find email named '$email_name'";
        }
        parent::__construct($message);
    }

    public function render($request)
    {
        return response()->json([
            'error' => $this->getMessage(),
            'email' => $this->email_name,
        ], $this->getCode());
    }
}

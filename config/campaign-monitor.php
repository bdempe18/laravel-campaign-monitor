<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campaign API Key
    |--------------------------------------------------------------------------
    |
    | Your Campaign Monitor API key used to authenticate requests. You can
    | store this in your .env file for security.
    |
    */
    'config' => [
        'apiKey' => env('CAMPAIGN_MONITOR_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Monitor Templates
    |--------------------------------------------------------------------------
    |
    | This file contains all Campaign Monitor smart email templates organized
    | by category for easy access and management. The template associative array
    | should have a template name as the key and the smart email id as the value.
    |
     */
    'templates' => [],

];

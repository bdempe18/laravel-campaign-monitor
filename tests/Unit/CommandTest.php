<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('list templates no config', function () {
    config()->set('campaign-monitor.templates', []);

    $this->artisan('campaign-monitor:list')
        ->expectsOutputToContain('No templates found in campaign-monitor configuration.')
        ->assertExitCode(1);
});

it('list templates displays templates and filters', function () {
    config()->set('campaign-monitor.templates', [
        'auth' => [
            'welcome' => 'ID-123',
            'reset' => 'ID-456',
        ],
        'billing' => [
            'invoice' => 'ID-789',
        ],
    ]);

    // All templates
    $this->artisan('campaign-monitor:list')
        ->expectsOutputToContain('auth.welcome')
        ->expectsOutputToContain('ID-123')
        ->expectsOutputToContain('auth.reset')
        ->expectsOutputToContain('ID-456')
        ->expectsOutputToContain('billing.invoice')
        ->expectsOutputToContain('ID-789')
        ->assertExitCode(0);

    // Filtered category
    $this->artisan('campaign-monitor:list auth')
        ->doesntExpectOutputToContain('billing.invoice')
        ->expectsOutputToContain('auth.welcome')
        ->expectsOutputToContain('auth.reset')
        ->assertExitCode(0);

    // Filter with no results
    $this->artisan('campaign-monitor:list unknown')
        ->expectsOutputToContain('No templates found for categories: unknown')
        ->expectsOutputToContain('Available categories: auth, billing')
        ->assertExitCode(0);
});

it('show template success displays details', function () {
    config()->set('campaign-monitor.config.apiKey', 'api-key');
    config()->set('campaign-monitor.templates', [
        'auth' => [
            'welcome' => 'SMART-1',
        ],
    ]);

    Http::fake([
        'createsend.com/api/v3.3/transactional/smartemail/SMART-1.json' => Http::response([
            'SmartEmailID' => 'SMART-1',
            'CreatedAt' => '2014-01-15T16:09:19-05:00',
            'Status' => 'Active',
            'Name' => 'Welcome Email',
            'Properties' => [
                'From' => 'Hello <team@webapp123.com>',
                'ReplyTo' => 'mike@webapp123.com',
                'Subject' => 'Welcome to our app',
                'Content' => [
                    'Html' => '<html><body>Hello</body></html>',
                    'Text' => 'Hello',
                    'EmailVariables' => ['first_name', 'app_name'],
                    'InlineCss' => true,
                ],
                'TextPreviewUrl' => 'http://siteaddress.createsend.com/path/text/preview',
                'HtmlPreviewUrl' => 'https://preview.example.com/welcome',
            ],
            'AddRecipientsToList' => '62eaaa0338245ca68e5e93daa6f591e9',
        ], 200),
    ]);

    $this->artisan('campaign-monitor:show auth.welcome')
        ->expectsOutputToContain('Campaign Monitor Email Details')
        ->expectsOutputToContain('Welcome Email')
        ->expectsOutputToContain('SMART-1')
        ->expectsOutputToContain('Active')
        ->expectsOutputToContain('Subject')
        ->expectsOutputToContain('Welcome to our app')
        ->expectsOutputToContain('From')
        ->expectsOutputToContain('team@webapp123.com')
        ->expectsOutputToContain('Variables')
        ->expectsOutputToContain('first_name')
        ->expectsOutputToContain('app_name')
        ->expectsOutputToContain('https://preview.example.com/welcome')
        ->assertExitCode(0);
});

it('show template missing name fails', function () {
    config()->set('campaign-monitor.config.apiKey', 'api-key');
    config()->set('campaign-monitor.templates', [
        'auth' => [
            'welcome' => 'SMART-1',
        ],
    ]);

    $this->artisan('campaign-monitor:show auth.unknown')
        ->expectsOutputToContain('No template found with name `auth.unknown`')
        ->assertExitCode(1);
});

it('sync templates no api key fails', function () {
    config()->set('campaign-monitor.config.apiKey', null);
    config()->set('campaign-monitor.templates', [
        'auth' => ['welcome' => 'SMART-1'],
    ]);

    $this->artisan('campaign-monitor:sync')
        ->expectsOutputToContain('Campaign Monitor API key not found')
        ->assertExitCode(1);
});

it('sync templates creates files and generates mock when html missing', function () {
    config()->set('campaign-monitor.config.apiKey', 'api-key');
    config()->set('campaign-monitor.templates', [
        'auth' => [
            'welcome' => 'SMART-1',
        ],
    ]);

    Http::fake([
        'createsend.com/api/v3.3/transactional/smartemail/SMART-1.json' => Http::response([
            'SmartEmailID' => 'SMART-1',
            'CreatedAt' => '2014-01-15T16:09:19-05:00',
            'Status' => 'Active',
            'Name' => 'Welcome email',
            'Properties' => [
                'From' => 'Hello <team@webapp123.com>',
                'ReplyTo' => 'mike@webapp123.com',
                'Subject' => 'Thanks for signing up to web app 123',
                'Content' => [
                    'Html' => 'Content managed in Email Builder',
                    'Text' => 'Content managed in Email Builder',
                    'EmailVariables' => ['first_name', 'plan'],
                    'InlineCss' => true,
                ],
                'TextPreviewUrl' => 'http://siteaddress.createsend.com/path/text/preview',
                'HtmlPreviewUrl' => 'https://preview.example.com/welcome',
            ],
            'AddRecipientsToList' => '62eaaa0338245ca68e5e93daa6f591e9',
        ], 200),
    ]);

    $resourcesPath = resource_path('views/emails/campaign-monitor');
    @mkdir($resourcesPath, 0755, true);

    $this->artisan('campaign-monitor:sync')
        ->expectsOutputToContain('Fetching templates from Campaign Monitor...')
        ->expectsOutputToContain('Finished syncing Campaign Monitor templates')
        ->assertExitCode(0);

    $expectedFilename = Str::slug('auth__welcome') . '.blade.php';
    $expectedPath = $resourcesPath . DIRECTORY_SEPARATOR . $expectedFilename;

    $this->assertFileExists($expectedPath);
    $this->assertStringContainsString('Payload preview', file_get_contents($expectedPath));
    $this->assertStringContainsString('first_name', file_get_contents($expectedPath));
    $this->assertStringContainsString('plan', file_get_contents($expectedPath));
});

it('sync templates overwrites existing files after confirmation', function () {
    config()->set('campaign-monitor.config.apiKey', 'api-key');
    config()->set('campaign-monitor.templates', [
        'auth' => [
            'welcome' => 'SMART-1',
        ],
    ]);

    Http::fake([
        'createsend.com/api/v3.3/transactional/smartemail/SMART-1.json' => Http::response([
            'SmartEmailID' => 'SMART-1',
            'CreatedAt' => '2014-01-15T16:09:19-05:00',
            'Status' => 'Active',
            'Name' => 'Welcome email',
            'Properties' => [
                'From' => 'Hello <team@webapp123.com>',
                'ReplyTo' => 'mike@webapp123.com',
                'Subject' => 'Thanks for signing up to web app 123',
                'Content' => [
                    'Html' => '<html><body>Server HTML</body></html>',
                    'HTML' => '<html><body>Server HTML</body></html>',
                    'Text' => 'Server HTML',
                    'EmailVariables' => [],
                    'InlineCss' => true,
                ],
                'TextPreviewUrl' => 'http://siteaddress.createsend.com/path/text/preview',
                'HtmlPreviewUrl' => 'https://preview.example.com/welcome',
            ],
            'AddRecipientsToList' => '62eaaa0338245ca68e5e93daa6f591e9',
        ], 200),
    ]);

    $resourcesPath = resource_path('views/emails/campaign-monitor');
    @mkdir($resourcesPath, 0755, true);
    $existingPath = $resourcesPath . DIRECTORY_SEPARATOR . Str::slug('auth__welcome') . '.blade.php';
    file_put_contents($existingPath, 'old');

    $this->artisan('campaign-monitor:sync')
        ->expectsOutputToContain('The following existing files will be overwritten:')
        ->expectsOutputToContain('auth__welcome.blade.php')
        ->expectsConfirmation('Do you want to continue? This will overwrite existing files.', 'yes')
        ->expectsOutputToContain('Finished syncing Campaign Monitor templates')
        ->assertExitCode(0);

    $this->assertStringContainsString('Server HTML', file_get_contents($existingPath));
});

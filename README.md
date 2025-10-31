# Laravel Campaign Monitor

A Laravel package that lets you send transactional Campaign Monitor emails using Laravel's built-in Mailable API. 

## Requirements

- PHP 8.1+
- Laravel 10.47+ or 11.0+
- A Campaign Monitor account with API access

## Installation

> **Warning**
> This package is currently being submitted to Packagist. Until it's available, you'll need to install it via a Git repository in your `composer.json`.

Install the package via Composer:

```bash
composer require bdempe18/laravel-campaign-monitor
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=campaign-monitor
```

Add the campaign monitor mailable to your `config/mail.php`. The package naturally toggles the transport from smpt to live emails based on the environment. By default no live emails are sent outside of production.

```php
# config/mail.php

'campaign-monitor' => [
    'transport' => 'campaign-monitor',
]
```

## Configuration

Add your Campaign Monitor API key to your `.env` file:

```env
CAMPAIGN_MONITOR_API_KEY=your-api-key-here
```

Next, configure your Smart Email templates in `config/campaign-monitor.php`. The templates array should be organized by category with template names as keys and Smart Email IDs as values. The templates array supports deep nesting.

```php
'templates' => [
    'welcome' => [
        'onboarding' => 'smart-email-id-123',
        'activation' => 'smart-email-id-456',
    ],
    'notifications' => [
        'order-confirmation' => 'smart-email-id-789',
        'shipping-update' => 'smart-email-id-012',
    ],
],
```

## Usage

### Sending Emails

Create a new Campaign Monitor mailable instance and send it just like any other Laravel mail:

```php
use CampaignMonitor\CampaignMonitor;
use CampaignMonitor\EmailTemplateManager;

$template = EmailTemplateManager::get('welcome.onboarding');

Mail::to('user@example.com')->send(
    new CampaignMonitor($template, [
        'name' => 'John Doe',
        'verification_link' => 'https://example.com/verify',
        // ... other template variables
    ])
);
```

The template path uses dot notation to match your configuration structure. So `'welcome.onboarding'` corresponds to the template defined at `templates.welcome.onboarding` in your config.

### Template Variables

All variables you pass in the data array will be sent to Campaign Monitor and available in your Smart Email template. Make sure the variable names match what's defined in your Campaign Monitor template.

### Queue Support

The `CampaignMonitor` mailable implements `ShouldQueue`, so you can use Laravel's queue system:

```php
Mail::to('user@example.com')->queue(
    new CampaignMonitor($template, $data)
);
```

### Environment Behavior

The package behaves differently depending on your environment:

- **Production**: Emails are sent through Campaign Monitor's API
- **Non-production**: Emails fall back to your configured SMTP mailer 

## Artisan Commands

### Sync Templates

Fetch your Campaign Monitor templates and cache them locally as Blade views:

```bash
php artisan campaign-monitor:sync
```

This command will:
- Fetch all Smart Email templates from Campaign Monitor
- Save them as Blade views in `resources/views/emails/campaign-monitor/`
- Generate mock templates for templates managed in Email Builder

The template files are named using a slug format like `onboarding.blade.php` based on your configuration structure.

### List Templates

View all configured templates. If you organize your templates through nested groups, can you filter by group.

```bash
php artisan campaign-monitor:list
```

### Show Template

Display details about a specific template:

```bash
php artisan campaign-monitor:show {template}
```

## Template Views

When you sync templates, they're stored in `resources/views/emails/campaign-monitor/`. The package automatically looks for views matching your template name, and falls back to a default view if not found. if you locally use mailpit or a similar service, you will receive nicely outputted test emails that give a good representation of what the live email will look like.

## Support

If you run into any issues or have questions, please open an issue on GitHub.

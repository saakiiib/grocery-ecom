<?php

namespace App\Support;

use App\Models\Setting;

/** Shop Settings SMTP (MailSettings) — .env first, settings fallback, same pattern as gateways. */
class MailSettings
{
    public const FIELDS = [
        'mail_host' => 'SMTP host',
        'mail_port' => 'SMTP port',
        'mail_encryption' => 'SMTP encryption (tls/ssl/none)',
        'mail_username' => 'SMTP username',
        'mail_password' => 'SMTP password',
        'mail_from_address' => 'From email address',
        'mail_from_name' => 'From name',
    ];

    public static function get(string $key, string $default = ''): string
    {
        return (string) (Setting::get($key, $default) ?? $default);
    }

    public static function configured(): bool
    {
        return static::get('mail_host') !== ''
            && static::get('mail_username') !== ''
            && static::get('mail_password') !== '';
    }

    /** '.env' when SMTP comes from the environment, 'settings' when from Shop Settings, null when mail is unconfigured. */
    public static function source(): ?string
    {
        if (static::configured()) {
            return 'settings';
        }

        return config('mail.default') === 'smtp' && config('mail.mailers.smtp.host') ? '.env' : null;
    }

    /** Overlay the Shop Settings SMTP over the .env mailer config (settings never rewrite .env). */
    public static function apply(): void
    {
        if (! static::configured()) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => static::get('mail_host'),
            'mail.mailers.smtp.port' => (int) (static::get('mail_port', '587') ?: 587),
            'mail.mailers.smtp.username' => static::get('mail_username'),
            'mail.mailers.smtp.password' => static::get('mail_password'),
            'mail.mailers.smtp.scheme' => static::scheme(),
        ]);

        $from = static::get('mail_from_address');
        if ($from !== '') {
            config(['mail.from.address' => $from]);
        }

        $name = static::get('mail_from_name');
        if ($name !== '') {
            config(['mail.from.name' => $name]);
        }
    }

    public static function scheme(): ?string
    {
        return match (static::get('mail_encryption', 'tls')) {
            'ssl' => 'smtps',
            'tls' => null,
            default => null,
        };
    }
}

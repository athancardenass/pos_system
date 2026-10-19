<?php

namespace App\Services\Integration;

class Endpoint
{
    public static function allowed(string $url, ?string $system = null): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            return false;
        }
        if (strtolower($parts['scheme'] ?? '') === 'https') {
            return true;
        }

        if (in_array($system, ['inventory', 'hrms', 'crm'], true) && config("services.$system.allow_http", false)
            && ($parts['scheme'] ?? '') === 'http'
            && rtrim($url, '/') === rtrim((string) config("services.$system.base_url"), '/')) {
            return true;
        }

        return app()->environment('local') && config('services.allow_loopback_http', false)
            && ($parts['scheme'] ?? '') === 'http'
            && in_array(strtolower($parts['host']), ['127.0.0.1', 'localhost', '[::1]'], true);
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->filterSensitiveData();
    }

    private function filterSensitiveData(): void
    {
        Telescope::filter(function ($entry) {
            $request = $entry->request;

            if ($request) {
                $data = $request->content();

                if (is_array($data)) {
                    $this->scrubArray($data);
                }

                // Scrub headers
                $headers = $request->headers();
                if (is_array($headers)) {
                    $this->scrubArray($headers);
                }
            }

            return true;
        });

        // Filter out specific entries
        Telescope::filterBatch(function ($entries) {
            return collect($entries)->filter(function ($entry) {
                // Filter out health checks and internal endpoints
                if ($entry->request) {
                    $url = $entry->request->url();
                    if (str_contains($url, '/health') || str_contains($url, '/up')) {
                        return false;
                    }
                    if (str_contains($url, '/telescope') || str_contains($url, '/pulse')) {
                        return false;
                    }
                }
                return true;
            })->toArray();
        });
    }

    private function scrubArray(array &$array): void
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'two_factor_code', 'two_factor_recovery_code',
            'credit_card', 'card_number', 'cvv',
            'secret', 'token', 'api_key', 'access_token', 'refresh_token',
            'stripe_signature', 'paddle_signature', 'authorization',
            'cookie', 'set-cookie',
        ];

        foreach ($array as $key => &$value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $sensitiveKeys, true)) {
                $value = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $this->scrubArray($value);
            }
        }
    }
}
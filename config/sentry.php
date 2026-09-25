<?php

declare(strict_types=1);

return [

    'dsn' => env('SENTRY_DSN'),

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    'environment' => env('APP_ENV', 'production'),

    'release' => env('APP_VERSION', '1.0.0'),

    'attach_stacktrace' => true,

    'max_breadcrumbs' => 50,

    'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
        // Remove sensitive data
        $event = $event->withServerData(array_diff_key($event->getServerData() ?? [], array_flip([
            'PHP_AUTH_PW',
            'PHP_AUTH_USER',
            'PHP_SELF',
        ])));

        // Scrub PII from request data
        if ($event->getRequest()) {
            $request = $event->getRequest();
            $data = $request->getData();

            if (is_array($data)) {
                $scrubbed = [];
                foreach ($data as $key => $value) {
                    if (in_array(strtolower($key), [
                        'password', 'password_confirmation', 'current_password',
                        'two_factor_code', 'two_factor_recovery_code',
                        'credit_card', 'card_number', 'cvv',
                        'secret', 'token', 'api_key', 'access_token', 'refresh_token',
                        'stripe_signature', 'paddle_signature', 'authorization',
                    ], true)) {
                        $scrubbed[$key] = '[REDACTED]';
                    } else {
                        $scrubbed[$key] = $value;
                    }
                }
                $event = $event->withRequest($request->withData($scrubbed));
            }
        }

        return $event;
    },

    'before_breadcrumb' => function (\Sentry\Breadcrumb $breadcrumb): ?\Sentry\Breadcrumb {
        // Filter out sensitive breadcrumbs
        $data = $breadcrumb->getData();

        if (isset($data['url']) && str_contains($data['url'], '/api/')) {
            return $breadcrumb;
        }

        return $breadcrumb;
    },

    'integrations' => [
        \Sentry\Integration\Laravel\LaravelIntegration::class => [],
        \Sentry\Integration\Monolog\MonologIntegration::class => [],
    ],

    'transport' => \Sentry\HttpClient\CurlHttpClient::class,

    'http_proxy' => env('SENTRY_HTTP_PROXY'),

    'ca_certs' => env('SENTRY_CA_CERTS'),

    'tracing' => [
        'sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'exclusions' => [
            '/health',
            '/up',
            '/telescope*',
            '/pulse*',
        ],
    ],

    'profiling' => [
        'sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
    ],

];
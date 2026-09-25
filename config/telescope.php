<?php

declare(strict_types=1);

return [

    'enabled' => env('TELESCOPE_ENABLED', true),

    'domain' => env('TELESCOPE_DOMAIN'),

    'path' => 'telescope',

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('TELESCOPE_DB_CONNECTION', 'pgsql'),
            'table' => 'telescope_entries',
        ],
    ],

    'watchers' => [
        \Laravel\Telescope\Watchers\BatchWatcher::class => [
            'enabled' => env('TELESCOPE_BATCH_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\CacheWatcher::class => [
            'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\DumpWatcher::class => [
            'enabled' => env('TELESCOPE_DUMP_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\EventWatcher::class => [
            'enabled' => env('TELESCOPE_EVENT_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\GateWatcher::class => [
            'enabled' => env('TELESCOPE_GATE_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\JobWatcher::class => [
            'enabled' => env('TELESCOPE_JOB_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\LogWatcher::class => [
            'enabled' => env('TELESCOPE_LOG_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\MailWatcher::class => [
            'enabled' => env('TELESCOPE_MAIL_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['created', 'updated', 'deleted', 'restored'],
        ],
        \Laravel\Telescope\Watchers\NotificationWatcher::class => [
            'enabled' => env('TELESCOPE_NOTIFICATION_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\PolicyWatcher::class => [
            'enabled' => env('TELESCOPE_POLICY_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'except' => ['/health', '/up', 'telescope*', 'pulse*', '_debugbar*'],
        ],
        \Laravel\Telescope\Watchers\ScheduleWatcher::class => [
            'enabled' => env('TELESCOPE_SCHEDULE_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore' => ['telescope*', 'pulse*'],
        ],
        \Laravel\Telescope\Watchers\RedisWatcher::class => [
            'enabled' => env('TELESCOPE_REDIS_WATCHER', true),
        ],
        \Laravel\Telescope\Watchers\ViewWatcher::class => [
            'enabled' => env('TELESCOPE_VIEW_WATCHER', true),
        ],
    ],

    'ignore' => [
        'health',
        'up',
        'telescope*',
        'pulse*',
        '_debugbar*',
    ],

    'hide' => [
        'password',
        'password_confirmation',
        'current_password',
        'two_factor_code',
        'two_factor_recovery_code',
        'credit_card',
        'card_number',
        'cvv',
        'secret',
        'token',
        'api_key',
        'access_token',
        'refresh_token',
        'stripe_signature',
        'paddle_signature',
        'authorization',
    ],

    'trim' => [
        'request' => [
            'cookies',
            'headers',
        ],
        'response' => [
            'headers',
        ],
    ],

    'middleware' => [
        'web' => [
            \App\Http\Middleware\AuthorizeControllerActions::class,
        ],
        'api' => [
            \App\Http\Middleware\AuthorizeControllerActions::class,
        ],
    ],

];
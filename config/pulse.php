<?php

declare(strict_types=1);

return [

    'enabled' => env('PULSE_ENABLED', true),

    'use' => env('PULSE_USE', 'redis'),

    'redis' => [
        'client' => 'phpredis',
        'cluster' => false,
        'connection' => 'pulse',
        'prefix' => env('PULSE_REDIS_PREFIX', 'pulse:'),
    ],

    'storage' => [
        'path' => storage_path('app/pulse'),
    ],

    'workers' => [
        'enabled' => env('PULSE_WORKERS_ENABLED', true),
        'count' => env('PULSE_WORKERS_COUNT', 1),
        'timeout' => env('PULSE_WORKERS_TIMEOUT', 60),
    ],

    'retention' => [
        'default' => env('PULSE_RETENTION_DEFAULT', 14),
        'slow_queries' => env('PULSE_RETENTION_SLOW_QUERIES', 30),
        'failed_jobs' => env('PULSE_RETENTION_FAILED_JOBS', 30),
        'exceptions' => env('PULSE_RETENTION_EXCEPTIONS', 30),
    ],

    'recorders' => [
        \App\Pulse\Recorders\CustomRecorder::class,
        \Laravel\Pulse\Recorders\CacheRecorder::class,
        \Laravel\Pulse\Recorders\DatabaseQueriesRecorder::class,
        \Laravel\Pulse\Recorders\DatabaseTransactionsRecorder::class,
        \Laravel\Pulse\Recorders\FailedJobsRecorder::class,
        \Laravel\Pulse\Recorders\JobsRecorder::class,
        \Laravel\Pulse\Recorders\OutgoingHttpRecorder::class,
        \Laravel\Pulse\Recorders\RequestRecorder::class,
        \Laravel\Pulse\Recorders\ScheduleRecorder::class,
        \Laravel\Pulse\Recorders\SlowQueriesRecorder::class,
        \Laravel\Pulse\Recorders\ExceptionsRecorder::class,
        \Laravel\Pulse\Recorders\RedisRecorder::class,
    ],

    'slow_query_threshold' => env('PULSE_SLOW_QUERY_THRESHOLD', 100),

    'exception_ignore' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Validation\ValidationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

];
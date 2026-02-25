<?php
declare(strict_types=1);

$envToken = getenv('YOU_SYSTEM_TOKEN');

return [
    'userAgent' => 'You-system/1.2',
    'timeoutSeconds' => 12,
    'allowOrigin' => '*',

    // segurança
    'requireToken' => true,
    'apiToken' => is_string($envToken) ? $envToken : '',
    'allowedIps' => [], // ex.: ['127.0.0.1', '192.168.1.10']
    'rateLimitWindowSeconds' => 60,
    'rateLimitMaxRequests' => 30,

    // confiabilidade
    'resolveRetries' => 3,
    'resolveBackoffMs' => 350,

    // cache / logs
    'cacheDir' => __DIR__ . '/../cache',
    'cacheTtlSeconds' => 120,
    'logDir' => __DIR__ . '/../logs',
    'logRetentionDays' => 7,
];

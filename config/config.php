<?php
declare(strict_types=1);

$envToken = getenv('YOU_SYSTEM_TOKEN');

return [
    'userAgent' => 'You-system/1.1',
    'timeoutSeconds' => 12,
    'allowOrigin' => '*',

    // segurança
    'apiToken' => is_string($envToken) ? $envToken : '',
    'rateLimitWindowSeconds' => 60,
    'rateLimitMaxRequests' => 30,

    // cache / logs
    'cacheDir' => __DIR__ . '/../cache',
    'cacheTtlSeconds' => 120,
    'logDir' => __DIR__ . '/../logs',
];

<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/HttpResponder.php';
require __DIR__ . '/../src/YouTubeLiveResolver.php';
require __DIR__ . '/../src/AppLogger.php';
require __DIR__ . '/../src/FileCache.php';
require __DIR__ . '/../src/RateLimiter.php';

header('Access-Control-Allow-Origin: ' . ($config['allowOrigin'] ?? '*'));

$logger = new AppLogger((string)($config['logDir'] ?? (__DIR__ . '/../logs')));
$cache = new FileCache((string)($config['cacheDir'] ?? (__DIR__ . '/../cache')), (int)($config['cacheTtlSeconds'] ?? 120));
$limiter = new RateLimiter((string)($config['cacheDir'] ?? (__DIR__ . '/../cache')), (int)($config['rateLimitWindowSeconds'] ?? 60), (int)($config['rateLimitMaxRequests'] ?? 30));

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (isset($_GET['health'])) {
    HttpResponder::json([
        'ok' => true,
        'service' => 'you-system',
        'time' => gmdate('c'),
    ]);
}

if (!$limiter->hit($ip)) {
    $logger->error('rate_limit', ['ip' => $ip]);
    HttpResponder::json(['ok' => false, 'error' => 'Rate limit excedido.'], 429);
}

$requiredToken = (string)($config['apiToken'] ?? '');
$token = trim((string)($_GET['token'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? ''));
if ($requiredToken !== '' && !hash_equals($requiredToken, $token)) {
    $logger->error('unauthorized', ['ip' => $ip]);
    HttpResponder::json(['ok' => false, 'error' => 'Token inválido.'], 401);
}

$videoId = trim((string)($_GET['id'] ?? ''));
$format = strtolower(trim((string)($_GET['format'] ?? 'redirect')));

if ($videoId === '') {
    HttpResponder::json([
        'ok' => false,
        'error' => 'Parâmetro id é obrigatório. Ex: ?id=WkhCfPPgqWc'
    ], 400);
}

$cacheKey = 'yt_' . $videoId;
$cached = $cache->get($cacheKey);

try {
    if ($cached !== null) {
        $hlsUrl = $cached;
        $cacheHit = true;
    } else {
        $resolver = new YouTubeLiveResolver(
            (string)($config['userAgent'] ?? 'You-system/1.0'),
            (int)($config['timeoutSeconds'] ?? 12)
        );

        $hlsUrl = $resolver->resolveHlsUrl($videoId);
        $cache->set($cacheKey, $hlsUrl);
        $cacheHit = false;
    }

    $logger->info('resolve_ok', ['videoId' => $videoId, 'ip' => $ip, 'cacheHit' => $cacheHit]);

    if ($format === 'json') {
        HttpResponder::json([
            'ok' => true,
            'videoId' => $videoId,
            'hls' => $hlsUrl,
            'cacheHit' => $cacheHit,
        ]);
    }

    HttpResponder::redirect($hlsUrl);
} catch (Throwable $e) {
    $logger->error('resolve_fail', ['videoId' => $videoId, 'ip' => $ip, 'error' => $e->getMessage()]);
    HttpResponder::json([
        'ok' => false,
        'videoId' => $videoId,
        'error' => $e->getMessage(),
    ], 500);
}

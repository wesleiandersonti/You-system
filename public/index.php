<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/HttpResponder.php';
require __DIR__ . '/../src/YouTubeLiveResolver.php';
require __DIR__ . '/../src/AppLogger.php';
require __DIR__ . '/../src/FileCache.php';
require __DIR__ . '/../src/RateLimiter.php';

header('Access-Control-Allow-Origin: ' . ($config['allowOrigin'] ?? '*'));

$logger = new AppLogger(
    (string)($config['logDir'] ?? (__DIR__ . '/../logs')),
    (int)($config['logRetentionDays'] ?? 7)
);
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

if (isset($_GET['metrics'])) {
    $logDir = (string)($config['logDir'] ?? (__DIR__ . '/../logs'));
    $todayLog = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR . 'you-system-' . date('Ymd') . '.log';
    $ok = 0;
    $fail = 0;
    if (is_file($todayLog)) {
        foreach (file($todayLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $j = json_decode($line, true);
            if (!is_array($j) || !isset($j['event'])) continue;
            if ($j['event'] === 'resolve_ok') $ok++;
            if ($j['event'] === 'resolve_fail') $fail++;
        }
    }

    HttpResponder::json([
        'ok' => true,
        'date' => date('Y-m-d'),
        'resolve_ok' => $ok,
        'resolve_fail' => $fail,
    ]);
}

$allowedIps = (array)($config['allowedIps'] ?? []);
if (!empty($allowedIps) && !in_array($ip, $allowedIps, true)) {
    $logger->error('forbidden_ip', ['ip' => $ip]);
    HttpResponder::json(['ok' => false, 'error' => 'IP não autorizado.'], 403);
}

if (!$limiter->hit($ip)) {
    $logger->error('rate_limit', ['ip' => $ip]);
    HttpResponder::json(['ok' => false, 'error' => 'Rate limit excedido.'], 429);
}

$requiredToken = (string)($config['apiToken'] ?? '');
$requireToken = (bool)($config['requireToken'] ?? true);
$token = trim((string)($_GET['token'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? ''));
if ($requireToken && ($requiredToken === '' || !hash_equals($requiredToken, $token))) {
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

$maxAttempts = (int)($config['resolveRetries'] ?? 3);
$backoffMs = (int)($config['resolveBackoffMs'] ?? 350);

try {
    if ($cached !== null) {
        $hlsUrl = $cached;
        $cacheHit = true;
    } else {
        $resolver = new YouTubeLiveResolver(
            (string)($config['userAgent'] ?? 'You-system/1.0'),
            (int)($config['timeoutSeconds'] ?? 12)
        );

        $lastError = null;
        $hlsUrl = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $hlsUrl = $resolver->resolveHlsUrl($videoId);
                break;
            } catch (Throwable $e) {
                $lastError = $e;
                if ($attempt < $maxAttempts) {
                    usleep(($backoffMs * $attempt) * 1000);
                }
            }
        }

        if (!is_string($hlsUrl) || $hlsUrl === '') {
            throw new RuntimeException($lastError?->getMessage() ?? 'Falha ao resolver HLS após retries.');
        }

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

<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../src/HttpResponder.php';
require __DIR__ . '/../src/YouTubeLiveResolver.php';

header('Access-Control-Allow-Origin: ' . ($config['allowOrigin'] ?? '*'));

$videoId = trim((string)($_GET['id'] ?? ''));
$format = strtolower(trim((string)($_GET['format'] ?? 'redirect')));

if ($videoId === '') {
    HttpResponder::json([
        'ok' => false,
        'error' => 'Parâmetro id é obrigatório. Ex: ?id=WkhCfPPgqWc'
    ], 400);
}

try {
    $resolver = new YouTubeLiveResolver(
        (string)($config['userAgent'] ?? 'You-system/1.0'),
        (int)($config['timeoutSeconds'] ?? 12)
    );

    $hlsUrl = $resolver->resolveHlsUrl($videoId);

    if ($format === 'json') {
        HttpResponder::json([
            'ok' => true,
            'videoId' => $videoId,
            'hls' => $hlsUrl,
        ]);
    }

    HttpResponder::redirect($hlsUrl);
} catch (Throwable $e) {
    HttpResponder::json([
        'ok' => false,
        'videoId' => $videoId,
        'error' => $e->getMessage(),
    ], 500);
}

<?php
declare(strict_types=1);

final class RateLimiter
{
    public function __construct(
        private readonly string $cacheDir,
        private readonly int $windowSeconds,
        private readonly int $maxRequests
    ) {}

    public function hit(string $ip): bool
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        $safeIp = preg_replace('/[^a-zA-Z0-9:._-]/', '_', $ip) ?: 'unknown';
        $file = rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . 'rl_' . $safeIp . '.json';

        $now = time();
        $data = ['start' => $now, 'count' => 0];

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $tmp = json_decode((string)$raw, true);
            if (is_array($tmp) && isset($tmp['start'], $tmp['count'])) {
                $data = ['start' => (int)$tmp['start'], 'count' => (int)$tmp['count']];
            }
        }

        if (($now - $data['start']) > $this->windowSeconds) {
            $data = ['start' => $now, 'count' => 0];
        }

        $data['count']++;
        @file_put_contents($file, json_encode($data));

        return $data['count'] <= $this->maxRequests;
    }
}

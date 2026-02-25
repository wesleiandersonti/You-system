<?php
declare(strict_types=1);

final class FileCache
{
    public function __construct(private readonly string $cacheDir, private readonly int $ttlSeconds) {}

    public function get(string $key): ?string
    {
        $path = $this->path($key);
        if (!is_file($path)) return null;

        $raw = @file_get_contents($path);
        $data = json_decode((string)$raw, true);
        if (!is_array($data) || !isset($data['ts'], $data['value'])) return null;

        if ((time() - (int)$data['ts']) > $this->ttlSeconds) return null;
        return is_string($data['value']) ? $data['value'] : null;
    }

    public function set(string $key, string $value): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        @file_put_contents($this->path($key), json_encode([
            'ts' => time(),
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function path(string $key): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) ?: 'k';
        return rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . $safe . '.json';
    }
}

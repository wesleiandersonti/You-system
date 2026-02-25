<?php
declare(strict_types=1);

final class AppLogger
{
    public function __construct(private readonly string $logDir) {}

    public function info(string $event, array $ctx = []): void
    {
        $this->write('INFO', $event, $ctx);
    }

    public function error(string $event, array $ctx = []): void
    {
        $this->write('ERROR', $event, $ctx);
    }

    private function write(string $level, string $event, array $ctx): void
    {
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0775, true);
        }

        $file = rtrim($this->logDir, '/\\') . DIRECTORY_SEPARATOR . 'you-system-' . date('Ymd') . '.log';
        $payload = [
            'ts' => gmdate('c'),
            'level' => $level,
            'event' => $event,
            'ctx' => $ctx,
        ];

        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
}

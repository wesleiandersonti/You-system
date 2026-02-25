<?php
declare(strict_types=1);

final class YouTubeLiveResolver
{
    public function __construct(
        private readonly string $userAgent,
        private readonly int $timeoutSeconds
    ) {}

    public function resolveHlsUrl(string $videoId): string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            throw new InvalidArgumentException('ID de vídeo inválido.');
        }

        $url = 'https://www.youtube.com/watch?v=' . urlencode($videoId);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => "User-Agent: {$this->userAgent}\r\nAccept-Language: pt-BR,pt;q=0.9,en;q=0.8\r\n",
            ]
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html === false || $html === '') {
            throw new RuntimeException('Falha ao consultar a página do YouTube.');
        }

        if (!preg_match('/"hlsManifestUrl":"([^"]+)"/', $html, $matches)) {
            throw new RuntimeException('hlsManifestUrl não encontrado (vídeo pode não estar ao vivo).');
        }

        $decoded = json_decode('"' . $matches[1] . '"', true);
        if (!is_string($decoded) || !str_starts_with($decoded, 'http')) {
            throw new RuntimeException('URL HLS inválida após decode.');
        }

        return $decoded;
    }
}

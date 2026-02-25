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

        $html = $this->fetchWatchHtml($videoId);

        $strategies = [
            fn(string $h) => $this->extractByHlsManifestUrl($h),
            fn(string $h) => $this->extractByUrlEncodedField($h),
            fn(string $h) => $this->extractByAnyM3u8($h),
        ];

        foreach ($strategies as $extractor) {
            $candidate = $extractor($html);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        throw new RuntimeException('hlsManifestUrl não encontrado (vídeo pode não estar ao vivo).');
    }

    private function fetchWatchHtml(string $videoId): string
    {
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

        return $html;
    }

    private function extractByHlsManifestUrl(string $html): ?string
    {
        if (!preg_match('/"hlsManifestUrl":"([^"]+)"/', $html, $matches)) {
            return null;
        }
        return $this->decodeUrlCandidate($matches[1]);
    }

    private function extractByUrlEncodedField(string $html): ?string
    {
        if (!preg_match('/hlsManifestUrl\\u003d([^\\"&]+)/', $html, $matches)) {
            return null;
        }
        $url = urldecode((string)$matches[1]);
        return $this->sanitizeUrl($url);
    }

    private function extractByAnyM3u8(string $html): ?string
    {
        if (!preg_match('/https:\\/\\/[^"\']+\.m3u8[^"\']*/', $html, $matches)) {
            return null;
        }
        $raw = str_replace('\\/', '/', $matches[0]);
        return $this->sanitizeUrl($raw);
    }

    private function decodeUrlCandidate(string $raw): ?string
    {
        $decoded = json_decode('"' . $raw . '"', true);
        if (!is_string($decoded)) {
            return null;
        }
        return $this->sanitizeUrl($decoded);
    }

    private function sanitizeUrl(string $url): ?string
    {
        if (!str_starts_with($url, 'http')) {
            return null;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}

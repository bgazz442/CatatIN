<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class VideoLinkParser
{
    public function parse(string $url): array
    {
        $parts = parse_url(trim($url));
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));

        if (($parts['scheme'] ?? null) !== 'https'
            || $host === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])) {
            throw ValidationException::withMessages([
                'url' => 'Gunakan tautan HTTPS yang valid tanpa kredensial atau port khusus.',
            ]);
        }

        $videoId = null;
        $platform = $this->platformForHost($host);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be', 'www.youtu.be'], true)) {
            $videoId = $this->youtubeId($host, $parts);
            $platform = 'youtube';
            if ($videoId === null) {
                throw ValidationException::withMessages(['url' => 'Tautan YouTube tidak berisi ID video yang valid.']);
            }
        } elseif (in_array($host, ['tiktok.com', 'www.tiktok.com', 'm.tiktok.com'], true)) {
            $videoId = $this->tiktokId($parts['path'] ?? '');
            $platform = 'tiktok';
            if ($videoId === null) {
                throw ValidationException::withMessages(['url' => 'Tautan TikTok tidak berisi ID video yang valid.']);
            }
        }

        return [
            'platform' => $platform,
            'video_id' => $videoId,
        ];
    }

    public function embedUrl(string $platform, ?string $videoId): ?string
    {
        if ($videoId === null) {
            return null;
        }

        return match ($platform) {
            'youtube' => preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)
                ? 'https://www.youtube-nocookie.com/embed/'.$videoId
                : null,
            'tiktok' => preg_match('/^[0-9]{10,25}$/', $videoId)
                ? 'https://www.tiktok.com/player/v1/'.$videoId
                : null,
            default => null,
        };
    }

    public function thumbnailUrl(string $platform, ?string $videoId): ?string
    {
        if ($platform !== 'youtube' || $this->embedUrl($platform, $videoId) === null) {
            return null;
        }

        return 'https://i.ytimg.com/vi/'.$videoId.'/hqdefault.jpg';
    }

    public function supportsAutomaticTranscription(string $platform): bool
    {
        return in_array($platform, ['youtube', 'tiktok', 'vimeo', 'instagram', 'facebook'], true);
    }

    private function youtubeId(string $host, array $parts): ?string
    {
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $candidate = trim($path, '/');
        } elseif (preg_match('~^/(?:shorts|embed|live)/([^/?]+)~', $path, $matches)) {
            $candidate = $matches[1];
        } else {
            $candidate = $query['v'] ?? '';
        }

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    private function tiktokId(string $path): ?string
    {
        if (preg_match('~^/@[A-Za-z0-9._-]+/video/([0-9]{10,25})/?$~', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function platformForHost(string $host): string
    {
        if (str_ends_with($host, '.vimeo.com') || $host === 'vimeo.com') {
            return 'vimeo';
        }

        if (str_ends_with($host, '.instagram.com') || $host === 'instagram.com') {
            return 'instagram';
        }

        if (str_ends_with($host, '.facebook.com') || $host === 'facebook.com') {
            return 'facebook';
        }

        return 'other';
    }
}

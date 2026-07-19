<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EducationalContent extends Model
{
    protected $fillable = [
        'stage_key',
        'month',
        'title',
        'description',
        'output_description',
        'display_order',
        'youtube_url',
        'uploaded_video_path',
        'uploaded_video_original_name',
        'uploaded_video_mime_type',
        'uploaded_video_size',
        'infographic_path',
        'infographic_original_name',
        'infographic_mime_type',
        'infographic_size',
        'is_published',
        'published_at',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'display_order' => 'integer',
            'uploaded_video_size' => 'integer',
            'infographic_size' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        $videoId = self::youtubeVideoId($this->youtube_url);

        return $videoId ? 'https://www.youtube.com/embed/'.$videoId : null;
    }

    public function getUploadedVideoUrlAttribute(): ?string
    {
        return $this->uploaded_video_path
            ? Storage::disk('public')->url($this->uploaded_video_path)
            : null;
    }

    public function getInfographicUrlAttribute(): ?string
    {
        return $this->infographic_path
            ? Storage::disk('public')->url($this->infographic_path)
            : null;
    }

    public static function youtubeVideoId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($host === 'youtu.be') {
            return self::cleanYoutubeId(strtok($path, '/'));
        }

        if (! in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (! empty($query['v'])) {
            return self::cleanYoutubeId((string) $query['v']);
        }

        $segments = $path === '' ? [] : explode('/', $path);
        $embedPrefixes = ['embed', 'shorts', 'live'];

        if (isset($segments[0], $segments[1]) && in_array($segments[0], $embedPrefixes, true)) {
            return self::cleanYoutubeId($segments[1]);
        }

        return null;
    }

    private static function cleanYoutubeId(?string $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^[A-Za-z0-9_-]{6,32}$/', $value) ? $value : null;
    }
}

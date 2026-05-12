<?php

namespace App\Models;

use App\Services\SupabaseStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class EntrepriseSetting extends Model
{
    protected $fillable = [
        'nom',
        'devise',
        'logo_path',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $this->logo_path)) {
            return $this->logo_path;
        }

        if (config('services.supabase.url')) {
            return app(SupabaseStorageService::class)->publicUrl($this->logo_path);
        }

        return '/storage/' . ltrim($this->logo_path, '/');
    }

    public function resolvePdfLogoSrc(): ?string
    {
        $path = trim((string) $this->logo_path);
        if ($path === '') {
            return null;
        }

        if (preg_match('/^data:image\//i', $path)) {
            return $path;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $this->remoteImageAsDataUri($path);
        }

        $localPath = storage_path('app/public/' . ltrim($path, '/'));
        if (is_file($localPath)) {
            return $this->localImageAsDataUri($localPath);
        }

        $logoUrl = $this->logo_url;
        if ($logoUrl && $logoUrl !== $path) {
            return $this->remoteImageAsDataUri($logoUrl);
        }

        return null;
    }

    private function localImageAsDataUri(string $path): ?string
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function remoteImageAsDataUri(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);
            if ($response->failed()) {
                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($response->body());
        } catch (\Throwable) {
            return null;
        }
    }
}

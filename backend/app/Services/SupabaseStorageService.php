<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseStorageService
{
    public function upload(UploadedFile $file, string $directory): string
    {
        return $this->uploadContent(
            file_get_contents($file->getRealPath()),
            $file->getMimeType(),
            $file->getClientOriginalExtension(),
            $directory
        );
    }

    public function uploadDataUrl(string $dataUrl, string $directory): string
    {
        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $dataUrl, $matches)) {
            throw new RuntimeException('Format image base64 invalide.');
        }

        return $this->uploadContent(
            base64_decode($matches[2], true),
            $matches[1],
            $this->extensionFromMimeType($matches[1]),
            $directory
        );
    }

    public function uploadContent(string|false $content, string $mimeType, string $extension, string $directory): string
    {
        if ($content === false) {
            throw new RuntimeException('Impossible de lire le fichier à envoyer vers Supabase.');
        }

        $this->ensureConfigured();

        $path = trim($directory, '/') . '/' . Str::uuid() . '.' . trim($extension, '.');
        $response = Http::withHeaders($this->headers($mimeType))
            ->withBody($content, $mimeType)
            ->post($this->objectUrl($path));

        if ($response->failed()) {
            throw new RuntimeException('Erreur upload Supabase: ' . $response->body());
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        $path = $this->normalizePath($path);

        if (!$path) {
            return;
        }

        Http::withHeaders($this->headers())
            ->delete($this->bucketUrl(), [
                'prefixes' => [$path],
            ]);
    }

    public function publicUrl(?string $path): ?string
    {
        $this->ensurePublicUrlConfigured();
        $path = $this->normalizePath($path);

        if (!$path) {
            return null;
        }

        return rtrim($this->baseUrl(), '/') . '/storage/v1/object/public/' . $this->bucket() . '/' . ltrim($path, '/');
    }

    private function normalizePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $path)) {
            return ltrim($path, '/');
        }

        $publicPrefix = '/storage/v1/object/public/' . $this->bucket() . '/';
        $parsedPath = parse_url($path, PHP_URL_PATH);

        if (!$parsedPath || !str_contains($parsedPath, $publicPrefix)) {
            return null;
        }

        return ltrim(Str::after($parsedPath, $publicPrefix), '/');
    }

    private function objectUrl(string $path): string
    {
        return $this->bucketUrl() . '/' . ltrim($path, '/');
    }

    private function bucketUrl(): string
    {
        return rtrim($this->baseUrl(), '/') . '/storage/v1/object/' . $this->bucket();
    }

    private function headers(?string $contentType = null): array
    {
        $headers = [
            'apikey' => $this->key(),
            'Authorization' => 'Bearer ' . $this->key(),
            'x-upsert' => 'true',
        ];

        if ($contentType) {
            $headers['Content-Type'] = $contentType;
        }

        return $headers;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.supabase.url'), '/');
    }

    private function key(): string
    {
        return (string) config('services.supabase.key');
    }

    private function bucket(): string
    {
        return (string) config('services.supabase.bucket', 'avatars');
    }

    private function ensureConfigured(): void
    {
        if (!$this->baseUrl() || !$this->key() || !$this->bucket()) {
            throw new RuntimeException('Configuration Supabase manquante: vérifiez SUPABASE_URL, SUPABASE_KEY et SUPABASE_BUCKET.');
        }
    }

    private function ensurePublicUrlConfigured(): void
    {
        if (!$this->baseUrl() || !$this->bucket()) {
            throw new RuntimeException('Configuration Supabase manquante: vérifiez SUPABASE_URL et SUPABASE_BUCKET.');
        }
    }

    private function extensionFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'png',
        };
    }
}

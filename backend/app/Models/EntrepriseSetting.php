<?php

namespace App\Models;

use App\Services\SupabaseStorageService;
use Illuminate\Database\Eloquent\Model;

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class EntrepriseSetting extends Model
{
    protected $fillable = [
        'nom',
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

        return '/storage/' . ltrim($this->logo_path, '/');
    }
}

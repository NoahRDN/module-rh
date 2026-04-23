<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentEmploye extends Model
{
    use Auditable;

    protected $table = 'documents_employes';

    protected $fillable = [
        'employe_id',
        'group_uuid',
        'type_document',
        'fichier',
        'date_expiration'
    ];

    protected $casts = [
        'date_expiration' => 'date'
    ];

    protected $appends = ['url', 'date_importation', 'nom_fichier', 'extension', 'preview_type'];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function getUrlAttribute(): string
    {
        if (!$this->fichier) {
            return '';
        }

        return Storage::disk('public')->url($this->fichier);
    }

    public function getDateImportationAttribute(): ?string
    {
        return $this->created_at?->toDateString();
    }

    public function getNomFichierAttribute(): string
    {
        return pathinfo((string) $this->fichier, PATHINFO_BASENAME) ?: '';
    }

    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo((string) $this->fichier, PATHINFO_EXTENSION) ?: '');
    }

    public function getPreviewTypeAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => 'pdf',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg' => 'image',
            default => 'file',
        };
    }
}

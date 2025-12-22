<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class DocumentEmploye extends Model
{
    use Auditable;

    protected $table = 'documents_employes';

    protected $fillable = [
        'employe_id',
        'type_document',
        'fichier',
        'date_expiration'
    ];

    protected $casts = [
        'date_expiration' => 'date'
    ];

    protected $appends = ['url'];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->fichier);
    }
}

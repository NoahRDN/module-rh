<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeDocument extends Model
{
    protected $table = 'demande_documents';

    protected $fillable = [
        'demande_id',
        'nom',
        'chemin',
        'type_mime',
        'taille',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }
}

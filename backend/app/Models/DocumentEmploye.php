<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentEmploye extends Model
{
    protected $table = 'document_employe';
    protected $primaryKey = 'id_document';
    public $timestamps = false; // Géré par Date_Ajout par défaut

    protected $fillable = [
        'id_employe', 'type_document', 'chemin_fichier', 'date_ajout'
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }
}
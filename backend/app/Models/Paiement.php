<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $table = 'paiement';
    protected $primaryKey = 'id_paiement';
    public $timestamps = false;

    protected $fillable = [
        'id_fiche', 'date_paiement', 'id_mode_paiement', 'id_statut_paiement', 'montant'
    ];

    public function fichePaie()
    {
        return $this->belongsTo(FichePaie::class, 'id_fiche');
    }
    
    public function modePaiement()
    {
        return $this->belongsTo(ModePaiement::class, 'id_mode_paiement');
    }
}
<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use Auditable;

    protected $table = 'demandes';

    protected $fillable = [
        'numero',
        'employe_id',
        'type_demande_id',
        'statut',
        'motif',
        'donnees',
        'montant',
        'commentaire_employe',
        'commentaire_rh',
        'date_soumission',
        'date_traitement',
        'traite_par',
    ];

    protected $casts = [
        'donnees' => 'array',
        'montant' => 'decimal:2',
        'date_soumission' => 'date',
        'date_traitement' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($demande) {
            if (empty($demande->numero)) {
                $demande->numero = self::genererNumero();
            }
        });
    }

    public static function genererNumero(): string
    {
        $annee = date('Y');
        $mois = date('m');
        $dernierNumero = self::whereYear('created_at', $annee)
            ->whereMonth('created_at', $mois)
            ->count() + 1;
        
        return sprintf('DEM-%s%s-%04d', $annee, $mois, $dernierNumero);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function typeDemande()
    {
        return $this->belongsTo(TypeDemandeRH::class, 'type_demande_id');
    }

    public function traitePar()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function documents()
    {
        return $this->hasMany(DemandeDocument::class);
    }

    public function scopeByStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeEnAttente($query)
    {
        return $query->whereIn('statut', ['soumise', 'en_cours']);
    }

    public function soumettre()
    {
        $this->statut = 'soumise';
        $this->date_soumission = now();
        $this->save();
    }

    public function approuver(User $user, ?string $commentaire = null)
    {
        $this->statut = 'approuvee';
        $this->date_traitement = now();
        $this->traite_par = $user->id;
        if ($commentaire) {
            $this->commentaire_rh = $commentaire;
        }
        $this->save();
    }

    public function rejeter(User $user, string $motif)
    {
        $this->statut = 'rejetee';
        $this->date_traitement = now();
        $this->traite_par = $user->id;
        $this->commentaire_rh = $motif;
        $this->save();
    }
}

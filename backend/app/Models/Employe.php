<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Employe extends Model
{
    use Auditable;

    protected $table = 'employes';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'date_naissance',
        'poste_id',
        'departement_id',
        'num_cnaps',
        'photo',
        'date_embauche'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche'  => 'date',
    ];

    protected $appends = ['actif'];

    public function poste()
    {
        return $this->belongsTo(Poste::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function contrats()
    {
        return $this->hasMany(Contrat::class);
    }

    public function documents()
    {
        return $this->hasMany(DocumentEmploye::class);
    }

    public function historiquePostes()
    {
        return $this->hasMany(HistoriquePoste::class);
    }

    public function competences()
    {
        return $this->belongsToMany(Competence::class, 'employe_competences')
            ->withPivot('niveau', 'date_evaluation', 'commentaire', 'evalue_par')
            ->withTimestamps();
    }

    public function formations()
    {
        return $this->belongsToMany(Formation::class, 'formation_employes')
            ->withPivot('statut', 'date_debut', 'date_fin', 'note', 'commentaire', 'certificat_obtenu')
            ->withTimestamps();
    }

    public function formationsEnCours()
    {
        return $this->formations()->wherePivot('statut', 'en_cours');
    }

    public function formationsTerminees()
    {
        return $this->formations()->wherePivot('statut', 'terminee');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'employe_id');
    }

    public function ajouterChangementPoste($nouveauPosteId, $nouveauDepartementId, $motif = null): void
    {
        $this->historiquePostes()->create([
            'poste_id'        => $nouveauPosteId,
            'departement_id'  => $nouveauDepartementId,
            'date_changement' => now(),
            'motif'           => $motif,
        ]);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('nom', 'LIKE', "%{$term}%")
                ->orWhere('prenom', 'LIKE', "%{$term}%")
                ->orWhere('matricule', 'LIKE', "%{$term}%");
        });
    }

    public function getActifAttribute(): bool
    {
        $now = now()->toDateString();
        return $this->contrats()
            ->whereDate('date_debut', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $now);
            })
            ->exists();
    }

    /**
     * Poste en vigueur à une date donnée en se basant sur l'historique.
     */
    public function posteActifPourDate($date): ?Poste
    {
        $cible = $date instanceof Carbon
            ? $date
            : (is_string($date) && strlen($date) === 7
                ? Carbon::createFromFormat('Y-m', $date)->endOfMonth()
                : Carbon::parse($date));

        $historique = $this->historiquePostes()
            ->with('poste')
            ->whereDate('date_changement', '<=', $cible->toDateString())
            ->orderByDesc('date_changement')
            ->first();

        if ($historique?->poste) {
            return $historique->poste;
        }

        return $this->relationLoaded('poste') ? $this->poste : $this->poste()->first();
    }
}

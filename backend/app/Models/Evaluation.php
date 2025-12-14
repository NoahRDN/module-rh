<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluations';

    protected $fillable = [
        'employe_id',
        'evaluateur_id',
        'date_evaluation',
        'periode',
        'score_global',
        'points_forts',
        'axes_amelioration',
        'objectifs',
        'commentaire_general',
        'statut',
    ];

    protected $casts = [
        'date_evaluation' => 'date',
        'score_global' => 'decimal:2',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function evaluateur()
    {
        return $this->belongsTo(User::class, 'evaluateur_id');
    }

    public function details()
    {
        return $this->hasMany(EvaluationDetail::class);
    }

    /**
     * Calcule le score global pondéré à partir des détails
     */
    public function calculerScoreGlobal(): float
    {
        $details = $this->details()->with('critere')->get();
        
        if ($details->isEmpty()) {
            return 0;
        }

        $totalPoids = 0;
        $totalScore = 0;

        foreach ($details as $detail) {
            $poids = $detail->critere->poids ?? 1;
            $totalPoids += $poids;
            $totalScore += ($detail->note * $poids);
        }

        return $totalPoids > 0 ? round($totalScore / $totalPoids, 2) : 0;
    }

    /**
     * Met à jour le score global
     */
    public function updateScoreGlobal(): void
    {
        $this->score_global = $this->calculerScoreGlobal();
        $this->save();
    }

    public function scopeForPeriode($query, $periode)
    {
        return $query->where('periode', $periode);
    }

    public function scopeValide($query)
    {
        return $query->where('statut', 'valide');
    }

    /**
     * Retourne le libellé du niveau de performance
     */
    public function getNiveauPerformanceAttribute(): string
    {
        $score = $this->score_global;
        
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Très bien';
        if ($score >= 60) return 'Bien';
        if ($score >= 50) return 'Satisfaisant';
        if ($score >= 40) return 'À améliorer';
        return 'Insuffisant';
    }
}

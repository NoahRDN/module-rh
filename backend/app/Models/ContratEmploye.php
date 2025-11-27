<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratEmploye extends Model
{
    protected $table = 'contrat_employe';
    protected $primaryKey = 'id_contrat_employe';
    public $timestamps = false;

    protected $fillable = ['id_employe', 'id_type_contrat', 'date_debut', 'date_fin'];

    public function typeContrat()
    {
        // Attention au nom de la méthode relationnelle vs la colonne
        return $this->belongsTo(TypeContrat::class, 'id_type_contrat');
    }
}
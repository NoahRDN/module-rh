<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosteHistorique extends Model
{
    // Nom de la table en minuscules
    protected $table = 'poste_historique';
    protected $primaryKey = 'id_historique';
    public $timestamps = false;

    // Clés étrangères en minuscules
    protected $fillable = ['id_poste', 'id_employe', 'action', 'description', 'date_action'];

    public function poste()
    {
        return $this->belongsTo(Poste::class, 'id_poste');
    }
}
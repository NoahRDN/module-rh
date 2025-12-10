<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldeConge extends Model
{
    protected $table = 'view_solde_conges';
    public $timestamps = false;
    protected $fillable = [];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function typeConge()
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }
}

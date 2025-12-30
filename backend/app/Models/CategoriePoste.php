<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriePoste extends Model
{
    use HasFactory;

    protected $table = 'categorie_postes';

    protected $fillable = [
        'nom',
        'code',
        'description',
    ];
}

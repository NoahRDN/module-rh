<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IrsaTranche extends Model
{
    protected $fillable = [
        'min_base',
        'max_base',
        'taux',
    ];
}

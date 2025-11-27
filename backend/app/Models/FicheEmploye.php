<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FicheEmploye extends Model
{
    // Spécifiez le nom de la vue
    protected $table = 'v_employe_poste';

    // Indiquez que la vue n'a pas de colonnes `created_at` et `updated_at`
    public $timestamps = false;

    // Définissez la clé primaire de la vue
    protected $primaryKey = 'id_employe';

    // Indiquez que la clé primaire n'est pas un auto-incrément
    public $incrementing = false;

    // Définissez le type de la clé primaire (si ce n'est pas un entier)
    protected $keyType = 'int';

    // Définissez les colonnes qui peuvent être utilisées pour les opérations de masse
    protected $fillable = [
        'id_employe',
        'nom',
        'prenom',
        'date_naissance',
        'genre',
        'ville',
        'departement',
        'fonction',
        'profil',
        'niveau_carriere',
        'type_contrat',
        'date_embauche',
        'contrat_debut',
        'contrat_fin',
        'utilisateur',
    ];
}
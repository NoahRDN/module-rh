<?php

namespace App\Models;

/**
 * Alias de compatibilité pour les anciens usages d'AbsenceType.
 * On pointe désormais sur la table/types de congés, sans recréer de table dédiée.
 */
class AbsenceType extends TypeConge
{
    protected $table = 'types_conges';
}

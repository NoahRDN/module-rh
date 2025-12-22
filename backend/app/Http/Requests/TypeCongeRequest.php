<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypeCongeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'libelle' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:types_conges,code,' . $this->route('types_conge'),
            'jours_forfait' => 'nullable|numeric|min:0',
            'utilise_solde' => 'boolean',
            'paye' => 'boolean',
            'limite' => 'nullable|integer|min:0',
            'limite_frequence_id' => 'nullable|exists:frequence_conges,id',
            'frequence_id' => 'nullable|exists:frequence_conges,id',
            'cumulable' => 'boolean',
            'cumulable_duree' => 'nullable|integer|min:0',
            'cumulable_frequence_id' => 'nullable|exists:frequence_conges,id',
            'justificatif_obligatoire' => 'boolean',
            'sexe_autorise' => 'nullable|in:homme,femme',
            'description' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemunerationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scope = $this->input('scope_type');
        $recurrence = $this->input('recurrence_type');
        $conditionType = $this->input('condition_type');

        return [
            'libelle' => 'required|string|max:120',
            'nature' => ['required', Rule::in(['prime', 'indemnite'])],
            'scope_type' => ['required', Rule::in(['global', 'poste', 'employe', 'contrat'])],
            'poste_id' => [
                Rule::requiredIf($scope === 'poste'),
                'nullable',
                'integer',
                'exists:postes,id',
            ],
            'employe_id' => [
                Rule::requiredIf($scope === 'employe'),
                'nullable',
                'integer',
                'exists:employes,id',
            ],
            'contrat_id' => [
                Rule::requiredIf($scope === 'contrat'),
                'nullable',
                'integer',
                'exists:contrats,id',
            ],
            'recurrence_type' => ['required', Rule::in(['recurrent', 'ponctuel'])],
            'mois_application' => [
                Rule::requiredIf($recurrence === 'ponctuel'),
                'nullable',
                'regex:/^\d{4}\-\d{2}$/',
            ],
            'condition_type' => ['nullable', Rule::in(['anciennete'])],
            'condition_operator' => [
                Rule::requiredIf(!empty($conditionType)),
                'nullable',
                Rule::in(['>', '<', '=', '>=', '<=']),
            ],
            'condition_value' => [
                Rule::requiredIf(!empty($conditionType)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'montant' => 'required|numeric|min:0',
            'is_taxable' => 'nullable|boolean',
            'actif' => 'nullable|boolean',
        ];
    }
}

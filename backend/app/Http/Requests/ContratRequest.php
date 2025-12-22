<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti'];
        $contratId = $this->route('contrat') ?? $this->route('id');

        return [
            'numero'            => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('contrats', 'numero')->ignore($contratId),
            ],
            'employe_id'          => 'required|exists:employes,id',
            'type_contrat'        => ['required', 'string', 'max:50', Rule::in($types)],
            'date_debut'          => 'required|date',
            'date_fin'            => 'nullable|date|after_or_equal:date_debut',
            'periode_essai_debut' => 'nullable|date',
            'periode_essai_fin'   => 'nullable|date|after_or_equal:periode_essai_debut',
            'renouvelable'        => 'boolean',
            'salaire_base'        => 'required|numeric|min:0',
            // Durées (alternatives aux dates de fin)
            'duree_jours'         => 'nullable|integer|min:0',
            'duree_mois'          => 'nullable|integer|min:0',
            'duree_ans'           => 'nullable|integer|min:0',
            'essai_jours'         => 'nullable|integer|min:0',
            'essai_mois'          => 'nullable|integer|min:0',
            'essai_ans'           => 'nullable|integer|min:0',
            'essai_debut'         => 'nullable|date',
            'statut'              => ['nullable', 'string', Rule::in(['en_cours', 'termine'])],
        ];
    }
}

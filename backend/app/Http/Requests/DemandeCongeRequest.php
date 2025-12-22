<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemandeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id' => 'required|exists:employes,id',
            'type_conge_id' => 'required|exists:types_conges,id',
            'jours_demandes' => 'nullable|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'motif' => 'nullable|string',
            'type_document' => 'nullable|string|max:100',
            'justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ];
    }
}

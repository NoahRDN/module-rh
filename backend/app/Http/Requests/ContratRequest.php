<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id'          => 'required|exists:employes,id',
            'type_contrat'        => 'required|string|max:50',
            'date_debut'          => 'required|date',
            'date_fin'            => 'nullable|date|after_or_equal:date_debut',
            'periode_essai_debut' => 'nullable|date',
            'periode_essai_fin'   => 'nullable|date|after_or_equal:periode_essai_debut',
            'renouvelable'        => 'boolean',
            'salaire_base'        => 'required|numeric|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HistoriquePosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id'      => 'required|exists:employes,id',
            'poste_id'        => 'required|exists:postes,id',
            'departement_id'  => 'required|exists:departements,id',
            'date_changement' => 'required|date',
            'motif'           => 'nullable|string|max:255',
        ];
    }
}

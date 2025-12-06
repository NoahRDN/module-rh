<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matricule'      => 'required|string|max:50',
            'nom'            => 'required|string|max:100',
            'prenom'         => 'required|string|max:100',
            'email'          => 'required|email',
            'telephone'      => 'nullable|string',
            'adresse'        => 'nullable|string',
            'date_naissance' => 'nullable|date',
            'poste_id'       => 'nullable|exists:postes,id',
            'departement_id' => 'nullable|exists:departements,id',
            'photo'          => 'nullable|string',
            'date_embauche'  => 'required|date'
        ];
    }
}

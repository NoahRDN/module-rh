<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeId = $this->route('id') ?? $this->route('employe');

        return [
            'matricule'      => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employes', 'matricule')->ignore($employeId),
            ],
            'nom'            => 'required|string|max:100',
            'prenom'         => 'required|string|max:100',
            'email'          => 'required|email',
            'telephone'      => 'nullable|string',
            'adresse'        => 'nullable|string',
            'date_naissance' => 'nullable|date',
            'poste_id'       => 'required|exists:postes,id',
            'departement_id' => 'nullable|exists:departements,id',
            'num_cnaps'      => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employes', 'num_cnaps')->ignore($employeId),
            ],
            'photo'          => 'nullable|string',
            'date_embauche'  => 'required|date'
        ];
    }
}

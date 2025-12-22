<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'            => 'required|string|max:100',
            'description'    => 'nullable|string',
            'departement_id' => 'required|exists:departements,id',
            'categorie'      => 'nullable|string|in:Ouvriers,Employés,TAM,Cadres,Dirigeants',
        ];
    }
}

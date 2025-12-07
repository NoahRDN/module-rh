<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbsenceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:150',
            'description' => 'nullable|string',
            'est_payant' => 'boolean',
            'jours_annuels' => 'nullable|integer|min:0',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PointageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id' => 'required|exists:employes,id',
            'type'       => 'required|in:entree,sortie,pause_debut,pause_fin',
            'pointe_a'   => 'required|date',
            'source'     => 'nullable|string|max:50',
            'commentaire'=> 'nullable|string|max:255',
        ];
    }
}

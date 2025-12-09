<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoldeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id' => 'required|exists:employes,id',
            'type_conge_id' => 'nullable|exists:types_conges,id',
            'solde_actuel' => 'required|numeric|min:0',
            'solde_annuel' => 'required|numeric|min:0',
        ];
    }
}

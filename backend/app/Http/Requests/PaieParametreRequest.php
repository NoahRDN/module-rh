<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaieParametreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnaps_plafond' => 'required|numeric|min:0',
            'cnaps_taux_employe' => 'required|numeric|min:0',
            'cnaps_taux_employeur' => 'required|numeric|min:0',
            'ostie_taux_employe' => 'required|numeric|min:0',
            'ostie_taux_employeur' => 'required|numeric|min:0',
            'irsa_base' => 'sometimes|numeric|min:0',
            'irsa_taux' => 'sometimes|numeric|min:0',
            'hs_taux' => 'sometimes|numeric|min:0',
            'prime_transport' => 'sometimes|numeric|min:0',
            'prime_presence' => 'sometimes|numeric|min:0',
        ];
    }
}

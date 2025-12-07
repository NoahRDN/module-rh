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
            'cnaps' => 'required|numeric|min:0',
            'ostie' => 'required|numeric|min:0',
            'irsa_base' => 'required|numeric|min:0',
            'irsa_taux' => 'required|numeric|min:0',
            'hs_taux' => 'required|numeric|min:0',
            'prime_transport' => 'required|numeric|min:0',
            'prime_presence' => 'required|numeric|min:0',
        ];
    }
}

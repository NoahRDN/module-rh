<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employe_id'      => 'required|exists:employes,id',
            'type_document'   => 'required|string|max:50',
            'fichier'         => 'required|string',
            'date_expiration' => 'nullable|date',
        ];
    }
}

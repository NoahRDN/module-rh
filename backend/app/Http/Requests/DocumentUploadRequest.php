<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = config('documents.types', ['CIN', 'Diplome', 'CV', 'Contrat', 'Attestation', 'Autre']);

        return [
            'employe_id'      => 'required|exists:employes,id',
            'type_document'   => ['required', 'string', 'max:50', Rule::in($types)],
            'fichier'         => 'required|file|max:4096',
            'date_expiration' => 'nullable|date',
        ];
    }
}

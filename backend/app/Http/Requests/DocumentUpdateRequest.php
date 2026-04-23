<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = config('documents.types', ['CIN', 'Diplome', 'CV', 'Contrat', 'Attestation', 'Autre']);
        $fileRules = 'file|mimes:pdf,jpg,jpeg,png,webp,gif,bmp|max:4096';

        return [
            'employe_id' => 'required|exists:employes,id',
            'group_uuid' => 'nullable|uuid',
            'type_document' => ['required', 'string', 'max:50', Rule::in($types)],
            'date_expiration' => 'nullable|date',
            'fichier' => "nullable|{$fileRules}",
            'fichiers' => 'nullable|array|min:1',
            'fichiers.*' => $fileRules,
        ];
    }
}

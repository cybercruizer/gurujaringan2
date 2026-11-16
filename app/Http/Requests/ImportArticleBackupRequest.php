<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportArticleBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'backup' => ['required', 'file', 'mimes:zip', 'max:102400'],
        ];
    }
}

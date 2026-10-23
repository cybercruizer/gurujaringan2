<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SaveMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'location' => ['required', 'in:header,footer'],
            'is_external' => ['required', 'boolean'],
            'url' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->boolean('is_external')) {
                        $parsedUrl = parse_url($value);

                        if (
                            ! filter_var($value, FILTER_VALIDATE_URL)
                            || ! is_array($parsedUrl)
                            || ! in_array(strtolower($parsedUrl['scheme'] ?? ''), ['http', 'https'], true)
                            || empty($parsedUrl['host'])
                        ) {
                            $fail('External links must use a valid HTTP or HTTPS URL.');
                        }

                        return;
                    }

                    if (
                        ! str_starts_with($value, '/')
                        || str_starts_with($value, '//')
                        || str_contains($value, '\\')
                        || preg_match('/[\x00-\x20]/', $value) === 1
                    ) {
                        $fail('Internal links must be a local path beginning with one slash.');
                    }
                },
            ],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_visible' => ['required', 'boolean'],
        ];
    }
}

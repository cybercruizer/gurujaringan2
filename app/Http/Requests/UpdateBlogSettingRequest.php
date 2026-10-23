<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'site_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'default_og_image' => ['nullable', 'url', 'max:2048'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'hero_eyebrow' => ['nullable', 'string', 'max:150'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'hero_cta_label' => ['nullable', 'string', 'max:100'],
            'allow_indexing' => ['required', 'boolean'],
            'sitemap_enabled' => ['required', 'boolean'],
        ];
    }
}

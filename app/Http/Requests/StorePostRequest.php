<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'in:article,page'],
            'content' => ['required', 'string', 'max:200000'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_noindex' => ['nullable', 'boolean'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'published_at' => ['nullable', 'date'],
            'comments_enabled' => ['sometimes', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'integer', 'distinct', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'integer', 'distinct', 'exists:tags,id'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'new_tag' => ['nullable', 'string', 'max:255'],
        ];
    }
}

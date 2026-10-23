<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Post;
use App\Support\CommentCaptcha;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post
            && $post->comments_enabled
            && $post->published_at !== null
            && $post->published_at->lessThanOrEqualTo(now());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'min:3', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
            'website' => ['prohibited'],
            'captcha_token' => ['required', 'string', 'regex:/\A[A-Za-z0-9]{40}\z/'],
            'captcha_answer' => ['required', 'integer', 'min:2', 'max:18'],
        ];
    }

    /**
     * Validate replies and consume the one-time CAPTCHA challenge.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $post = $this->route('post');

                if (
                    ! $validator->errors()->has('parent_id')
                    && $this->input('parent_id') !== null
                    && $post instanceof Post
                ) {
                    $isValidParent = Comment::query()
                        ->whereKey($this->integer('parent_id'))
                        ->where('post_id', $post->id)
                        ->whereNotNull('approved_at')
                        ->whereNull('parent_id')
                        ->exists();

                    if (! $isValidParent) {
                        $validator->errors()->add('parent_id', 'Choose an approved comment on this post to reply to.');
                    }
                }

                if ($validator->errors()->hasAny(['captcha_token', 'captcha_answer'])) {
                    return;
                }

                $expectedAnswer = app(CommentCaptcha::class)->consume(
                    $this->string('captcha_token')->toString(),
                );

                if ($expectedAnswer === null || $this->integer('captcha_answer') !== $expectedAnswer) {
                    $validator->errors()->add('captcha_answer', 'The CAPTCHA answer is incorrect or has expired.');
                }
            },
        ];
    }
}

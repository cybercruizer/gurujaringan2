@props(['comment', 'post', 'depth' => 0, 'commentCaptchas' => []])

@php
    $authorName = $comment->author_name ?: $comment->user?->name ?: 'Anonymous';
@endphp

<article class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
    <header class="flex items-center gap-3">
        @if ($comment->user?->avatar)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($comment->user->avatar) }}" alt="" class="size-10 rounded-full object-cover">
        @else
            <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-full bg-stone-100 text-sm font-semibold text-stone-700">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($authorName, 0, 1)) }}
            </span>
        @endif
        <div class="flex flex-col">
            <span class="text-sm font-semibold text-stone-900">{{ $authorName }}</span>
            <time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-stone-500">
                {{ $comment->created_at->diffForHumans() }}
            </time>
        </div>
    </header>

    <p class="whitespace-pre-line break-words text-sm leading-7 text-stone-700">{{ $comment->content }}</p>

    @if ($depth === 0 && $post->comments_enabled && $post->published_at && $post->published_at->lessThanOrEqualTo(now()))
        <details class="group flex flex-col gap-3" @if (old('parent_id') == $comment->id && $errors->any()) open @endif>
            <summary class="inline-flex w-fit cursor-pointer list-none items-center gap-2 rounded-lg border border-stone-200 px-3 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-300 hover:bg-stone-50 hover:text-stone-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-500 [&::-webkit-details-marker]:hidden">
                <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8h10M7 12h6m-8 8 2.5-3H17a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v7a3 3 0 0 0 3 3h1" />
                </svg>
                Reply to {{ $authorName }}
            </summary>
            <x-comment-form :post="$post" :parent-id="$comment->id" :captcha="$commentCaptchas[$comment->id]" />
        </details>
    @endif

    @if ($depth === 0 && $comment->replies->isNotEmpty())
        <div class="flex flex-col gap-4 border-l-2 border-stone-200 pl-4 sm:ml-6 sm:pl-6">
            @foreach ($comment->replies as $reply)
                <x-comment-thread :comment="$reply" :post="$post" :depth="1" />
            @endforeach
        </div>
    @endif
</article>

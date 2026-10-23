@props(['post', 'parentId' => null, 'captcha'])

<form method="POST" action="{{ route('comments.store', $post) }}" class="relative flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
    @csrf

    <input type="hidden" name="captcha_token" value="{{ $captcha['token'] }}">

    @if ($parentId !== null)
        <input type="hidden" name="parent_id" value="{{ $parentId }}">
        <h3 class="text-sm font-semibold text-stone-900">Reply to this comment</h3>
    @else
        <h3 class="text-sm font-semibold text-stone-900">Leave a comment</h3>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-2">
            <label for="comment-name-{{ $parentId ?? 'root' }}" class="text-sm font-medium text-stone-800">Name</label>
            <input
                id="comment-name-{{ $parentId ?? 'root' }}"
                name="author_name"
                type="text"
                value="{{ old('parent_id') == $parentId ? old('author_name') : '' }}"
                required
                maxlength="255"
                autocomplete="name"
                class="w-full rounded-xl border-stone-300 text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-stone-500 focus:ring-stone-500"
            >
            <x-input-error :messages="$errors->get('author_name')" />
        </div>
        <div class="flex flex-col gap-2">
            <label for="comment-email-{{ $parentId ?? 'root' }}" class="text-sm font-medium text-stone-800">Email</label>
            <input
                id="comment-email-{{ $parentId ?? 'root' }}"
                name="author_email"
                type="email"
                value="{{ old('parent_id') == $parentId ? old('author_email') : '' }}"
                required
                maxlength="255"
                autocomplete="email"
                aria-describedby="comment-email-help-{{ $parentId ?? 'root' }}"
                class="w-full rounded-xl border-stone-300 text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-stone-500 focus:ring-stone-500"
            >
            <p id="comment-email-help-{{ $parentId ?? 'root' }}" class="text-xs text-stone-500">Your email will not be shown publicly.</p>
            <x-input-error :messages="$errors->get('author_email')" />
        </div>
    </div>

    <label for="{{ $parentId === null ? 'comment-content' : 'reply-content-'.$parentId }}" class="text-sm font-medium text-stone-800">
        {{ $parentId === null ? 'Your comment' : 'Your reply' }}
    </label>
    <textarea
        id="{{ $parentId === null ? 'comment-content' : 'reply-content-'.$parentId }}"
        name="content"
        rows="4"
        required
        minlength="3"
        maxlength="5000"
        placeholder="Share your thoughts..."
        class="w-full rounded-xl border-stone-300 text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-stone-500 focus:ring-stone-500"
    >{{ old('parent_id') == $parentId ? old('content') : '' }}</textarea>
    <x-input-error :messages="$errors->get('content')" />
    <x-input-error :messages="$errors->get('parent_id')" />
    <x-input-error :messages="$errors->get('captcha_token')" />

    <div aria-hidden="true" class="absolute -left-[9999px] top-auto size-px overflow-hidden">
        <label for="comment-website-{{ $parentId ?? 'root' }}">Leave this field empty</label>
        <input id="comment-website-{{ $parentId ?? 'root' }}" name="website" type="text" value="" tabindex="-1" autocomplete="off">
    </div>
    <x-input-error :messages="$errors->get('website')" />

    <div class="flex flex-col gap-2">
        <label for="comment-captcha-{{ $parentId ?? 'root' }}" class="text-sm font-medium text-stone-800">
            CAPTCHA: What is {{ $captcha['first_number'] }} + {{ $captcha['second_number'] }}?
        </label>
        <input
            id="comment-captcha-{{ $parentId ?? 'root' }}"
            name="captcha_answer"
            type="number"
            inputmode="numeric"
            required
            min="2"
            max="18"
            class="w-28 rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500"
        >
        <x-input-error :messages="$errors->get('captcha_answer')" />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-stone-500">No account required. Comments are reviewed before they appear.</p>
        <button type="submit" class="rounded-full bg-stone-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:ring-offset-2">
            Submit comment
        </button>
    </div>
</form>

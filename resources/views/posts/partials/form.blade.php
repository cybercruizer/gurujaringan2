@php
    $selectedCategories = array_map(
        'strval',
        old('categories', $post->categories->modelKeys()),
    );
    $selectedTags = array_map(
        'strval',
        old('tags', $post->tags->modelKeys()),
    );
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="flex flex-col gap-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Please correct the following errors:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col gap-2">
        <label for="title" class="text-sm font-semibold text-stone-800">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $post->title) }}" required maxlength="255" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
        @error('title')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-2">
        <label for="type" class="text-sm font-semibold text-stone-800">Page type</label>
        <select id="type" name="type" required class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
            <option value="article" @selected(old('type', $post->type ?? 'article') === 'article')>Article</option>
            <option value="page" @selected(old('type', $post->type ?? 'article') === 'page')>Page</option>
        </select>
        @error('type')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-start gap-3 rounded-xl border border-stone-200 p-4 text-sm text-stone-700">
        <input type="hidden" name="comments_enabled" value="0">
        <input type="checkbox" name="comments_enabled" value="1" @checked(old('comments_enabled', $post->comments_enabled ?? true)) class="mt-0.5 rounded border-stone-300 text-stone-900 focus:ring-stone-500">
        <span>
            <span class="block font-semibold text-stone-900">Allow comments</span>
            <span class="mt-1 block text-stone-500">Readers can submit comments using their name and email. Comments require admin approval before appearing.</span>
        </span>
    </label>
    @error('comments_enabled')
        <p class="text-sm text-red-700">{{ $message }}</p>
    @enderror

    <div class="flex flex-col gap-2">
        <label for="excerpt" class="text-sm font-semibold text-stone-800">Excerpt <span class="font-normal text-stone-500">(optional)</span></label>
        <textarea id="excerpt" name="excerpt" rows="3" maxlength="1000" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('excerpt', $post->excerpt) }}</textarea>
        @error('excerpt')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <details class="rounded-xl border border-stone-200 p-4">
        <summary class="cursor-pointer text-sm font-semibold text-stone-800">SEO settings</summary>
        <div class="mt-4 flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="seo_title" class="text-sm font-semibold text-stone-800">Search title <span class="font-normal text-stone-500">(optional)</span></label>
                <input id="seo_title" name="seo_title" type="text" value="{{ old('seo_title', $post->seo_title) }}" maxlength="255" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                @error('seo_title')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-col gap-2">
                <label for="seo_description" class="text-sm font-semibold text-stone-800">Search description <span class="font-normal text-stone-500">(optional, max 320 characters)</span></label>
                <textarea id="seo_description" name="seo_description" rows="3" maxlength="320" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('seo_description', $post->seo_description) }}</textarea>
                @error('seo_description')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <label class="flex items-center gap-3 text-sm text-stone-700">
                <input type="hidden" name="seo_noindex" value="0">
                <input type="checkbox" name="seo_noindex" value="1" @checked(old('seo_noindex', $post->seo_noindex)) class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                Exclude this content from search results
            </label>
            @error('seo_noindex')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
    </details>

    <div class="flex flex-col gap-2" data-media-picker data-library-url="{{ route('media.library') }}">
        <label for="content" class="text-sm font-semibold text-stone-800">Content</label>
        <textarea id="content" name="content" rows="14" data-rich-text-editor data-upload-url="{{ route('posts.content-images.store') }}" data-library-url="{{ route('media.library') }}" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('content', $post->content) }}</textarea>
        <p class="text-xs text-stone-500">Use the editor toolbar to format your post. Uploaded images are limited to 5 MB.</p>
        @error('content')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
        <dialog data-media-dialog aria-labelledby="media-dialog-title" class="m-auto max-h-[90vh] w-[min(96vw,56rem)] overflow-y-auto rounded-2xl border border-stone-200 bg-white p-0 text-stone-900 shadow-2xl backdrop:bg-stone-950/60">
            <div class="flex flex-col gap-5 p-5 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="media-dialog-title" class="text-xl font-bold">Choose from media library</h2>
                        <p class="mt-1 text-sm text-stone-600">Reuse an image already uploaded to the library.</p>
                    </div>
                    <button type="button" data-media-close aria-label="Close media library" class="rounded-full px-3 py-1 text-xl text-stone-500 hover:bg-stone-100">&times;</button>
                </div>
                <div class="flex gap-2">
                    <input type="search" data-media-search aria-label="Search media library" placeholder="Search file names" class="min-w-0 flex-1 rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-500 focus:ring-stone-500">
                    <button type="button" data-media-search-submit class="rounded-full bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700">Search</button>
                </div>
                <p data-media-status role="status" class="text-sm text-stone-600"></p>
                <div data-media-items class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>
                <div class="flex justify-end gap-3">
                    <button type="button" data-media-next hidden class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Load more</button>
                    <button type="button" data-media-close class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Close</button>
                </div>
            </div>
        </dialog>
    </div>

    <div class="flex flex-col gap-2">
        <label for="published_at" class="text-sm font-semibold text-stone-800">Publish date <span class="font-normal text-stone-500">(clear to save as a draft)</span></label>
        <input
            id="published_at"
            name="published_at"
            type="datetime-local"
            value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i') ?? ($post->exists ? '' : now()->format('Y-m-d\TH:i'))) }}"
            class="w-full rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500 sm:max-w-xs"
        >
        @error('published_at')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <fieldset class="flex flex-col gap-3">
        <legend class="text-sm font-semibold text-stone-800">Categories</legend>
        <input type="hidden" name="categories[]" value="">
        @if ($categories->isNotEmpty())
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($categories as $category)
                    <label class="flex items-center gap-3 rounded-xl border border-stone-200 px-4 py-3 text-sm text-stone-700">
                        <input
                            type="checkbox"
                            name="categories[]"
                            value="{{ $category->id }}"
                            @checked(in_array((string) $category->id, $selectedCategories, true))
                            class="rounded border-stone-300 text-stone-900 focus:ring-stone-500"
                        >
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>
        @else
            <p class="text-sm text-stone-500">There are no categories yet.</p>
        @endif
        @error('categories')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
        @error('categories.*')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </fieldset>

    <fieldset class="flex flex-col gap-3">
        <legend class="text-sm font-semibold text-stone-800">Tags</legend>
        <input type="hidden" name="tags[]" value="">
        @if ($tags->isNotEmpty())
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($tags as $tag)
                    <label class="flex items-center gap-3 rounded-xl border border-stone-200 px-4 py-3 text-sm text-stone-700">
                        <input
                            type="checkbox"
                            name="tags[]"
                            value="{{ $tag->id }}"
                            @checked(in_array((string) $tag->id, $selectedTags, true))
                            class="rounded border-stone-300 text-stone-900 focus:ring-stone-500"
                        >
                        {{ $tag->name }}
                    </label>
                @endforeach
            </div>
        @else
            <p class="text-sm text-stone-500">There are no tags yet.</p>
        @endif
        @error('tags')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
        @error('tags.*')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </fieldset>

    <div class="flex flex-col gap-2" data-media-picker data-library-url="{{ route('media.library') }}">
        <label for="featured_image" class="text-sm font-semibold text-stone-800">Featured image <span class="font-normal text-stone-500">(JPEG, PNG, GIF, or WebP; max 5 MB)</span></label>
        <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="block w-full text-sm text-stone-600 file:mr-4 file:rounded-full file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
        <input type="hidden" name="featured_media_id" value="{{ old('featured_media_id', $post->featured_media_id) }}" data-featured-media-id>
        <button type="button" data-media-open class="w-fit rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Choose from media library</button>
        <div data-featured-media-preview class="flex flex-col gap-2">
        @if ($post->featured_image)
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image_thumbnail ?: $post->featured_image) }}"
                alt="{{ $post->featuredMedia?->alt_text ?: 'Current featured image' }}"
                class="mt-2 aspect-[16/10] w-full max-w-sm rounded-xl object-cover"
            >
            <p class="text-sm text-stone-500">Uploading a new image replaces the current one.</p>
        @else
            <p class="text-sm text-stone-500">No featured image selected.</p>
        @endif
        </div>
        @if ($post->featured_image)
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="remove_featured_image" value="1" @checked(old('remove_featured_image')) class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                Remove the current featured image
            </label>
        @endif
        @error('featured_image')
            <p class="text-sm text-red-700">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-stone-200 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ $post->exists ? $post->url() : route('posts.index') }}" class="rounded-full border border-stone-300 px-5 py-3 text-center text-sm font-semibold text-stone-700 hover:bg-stone-100">Cancel</a>
        <button type="submit" class="rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">{{ $submitLabel }}</button>
    </div>
</form>

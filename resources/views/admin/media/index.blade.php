<x-admin-layout>
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="flex flex-col gap-3">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Administration</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">Media library</h1>
            <p class="max-w-2xl leading-7 text-stone-600">Upload an image once and reuse it in featured images and article content. Images currently in use are protected from deletion.</p>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:p-7">
            @csrf
            <h2 class="text-lg font-semibold text-stone-950">Add images</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <label for="image" class="text-sm font-semibold text-stone-800">Image <span class="font-normal text-stone-500">(JPEG, PNG, GIF, or WebP; max 5 MB)</span></label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required class="block w-full text-sm text-stone-600 file:mr-4 file:rounded-full file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
                </div>
                <div class="flex flex-col gap-2">
                    <label for="alt_text" class="text-sm font-semibold text-stone-800">Alternative text <span class="font-normal text-stone-500">(optional)</span></label>
                    <input id="alt_text" name="alt_text" type="text" maxlength="255" value="{{ old('alt_text') }}" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                    @error('alt_text')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @error('image')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            <button type="submit" class="w-fit rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">Upload image</button>
        </form>

        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-stone-950">Library</h2>
                    <p class="mt-1 text-sm text-stone-600">{{ $mediaItems->total() }} {{ \Illuminate\Support\Str::plural('image', $mediaItems->total()) }}</p>
                </div>
                <form method="GET" action="{{ route('admin.media.index') }}" class="flex gap-2">
                    <label for="search" class="sr-only">Search images</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Search file names" class="min-w-0 rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-500 focus:ring-stone-500">
                    <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Search</button>
                </form>
            </div>

            @if ($mediaItems->isEmpty())
                <p class="rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-stone-600">No images found. Upload an image to start your reusable library.</p>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    @foreach ($mediaItems as $media)
                        <article class="flex min-w-0 flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-3">
                            <img src="{{ $media->thumbnailUrl() }}" alt="{{ $media->alt_text ?: $media->original_name }}" loading="lazy" class="aspect-square w-full rounded-xl bg-stone-100 object-cover">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-stone-900" title="{{ $media->original_name }}">{{ $media->original_name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ \Illuminate\Support\Number::fileSize($media->size) }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.media.update', $media) }}" class="flex flex-col gap-2">
                                @csrf
                                @method('PUT')
                                <label for="alt_text_{{ $media->id }}" class="text-xs font-semibold text-stone-700">Alternative text</label>
                                <input id="alt_text_{{ $media->id }}" name="alt_text" type="text" maxlength="255" value="{{ $media->alt_text }}" class="min-w-0 rounded-lg border-stone-300 text-xs shadow-sm focus:border-stone-500 focus:ring-stone-500">
                                <button type="submit" class="rounded-full border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-100">Save alt text</button>
                            </form>
                            <form method="POST" action="{{ route('admin.media.destroy', $media) }}" onsubmit="return confirm('Delete this image from the media library?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Delete image</button>
                            </form>
                        </article>
                    @endforeach
                </div>
                {{ $mediaItems->links() }}
            @endif
        </div>
    </section>
</x-admin-layout>

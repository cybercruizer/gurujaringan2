<x-app-layout
    :seo-title="$taxonomy->name"
    :seo-description="$taxonomy->description"
    seo-type="website"
>
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="flex flex-col gap-3 border-b border-stone-200 pb-7">
            <a href="{{ route('posts.index') }}" class="w-fit text-sm font-semibold text-emerald-800 hover:text-emerald-950">&larr; Browse all articles</a>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">{{ $taxonomyType }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">{{ $taxonomy->name }}</h1>
            @if ($taxonomy->description)
                <p class="max-w-3xl leading-7 text-stone-600">{{ $taxonomy->description }}</p>
            @endif
            <p class="text-sm text-stone-500">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }}</p>
        </header>

        @if ($posts->isNotEmpty())
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <x-pagination :paginator="$posts" />
        @else
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
                <p class="text-lg font-semibold text-stone-800">No published articles here yet.</p>
                <p class="mt-2 text-sm text-stone-500">Check back later for new articles.</p>
            </div>
        @endif
    </section>
</x-app-layout>

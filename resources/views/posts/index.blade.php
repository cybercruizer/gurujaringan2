<x-app-layout>
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">From the blog</p>
                <h1 class="text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">Latest articles</h1>
                <p class="text-stone-600">Explore stories and ideas from our community.</p>
            </div>

            @auth
                <a href="{{ route('posts.create') }}" class="inline-flex w-fit rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">
                    Write a post
                </a>
            @endauth
        </div>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        @if ($posts->isNotEmpty())
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>

            <x-pagination :paginator="$posts" />
        @else
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
                <p class="text-lg font-semibold text-stone-800">No published posts yet.</p>
                <p class="mt-2 text-sm text-stone-500">Check back soon for the latest articles.</p>
            </div>
        @endif
    </section>
</x-app-layout>

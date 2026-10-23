<x-admin-layout>
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <a href="{{ route('admin.dashboard') }}" class="w-fit text-sm font-medium text-stone-600 hover:text-stone-950">&larr; Admin dashboard</a>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Article management</p>
                <h1 class="text-3xl font-bold tracking-tight text-stone-950">Categories</h1>
                <p class="text-stone-600">Organize articles into browsable topics.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="w-fit rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white hover:bg-stone-700">Add category</a>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        <div class="flex flex-col gap-3">
            @forelse ($categories as $category)
                <article class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="flex flex-col gap-1">
                        <h2 class="font-semibold text-stone-950">{{ $category->name }}</h2>
                        <p class="text-sm text-stone-500">/{{ $category->slug }} · {{ $category->posts_count }} {{ \Illuminate\Support\Str::plural('article', $category->posts_count) }}</p>
                        @if ($category->description)
                            <p class="text-sm leading-6 text-stone-600">{{ $category->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Edit</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? Articles will remain, but will no longer have this category.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-stone-200 bg-white px-5 py-10 text-center text-sm text-stone-600">No categories yet. Add one to start organizing articles.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$categories" />
    </section>
</x-admin-layout>

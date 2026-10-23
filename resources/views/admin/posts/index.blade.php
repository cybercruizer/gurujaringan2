<x-admin-layout>
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Content management</p>
                <h1 class="text-3xl font-bold tracking-tight text-stone-950">Articles and pages</h1>
                <p class="text-stone-600">Manage published content, scheduled articles, and drafts.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <form method="GET" action="{{ route('admin.posts.index') }}" class="flex gap-2">
                    <label for="search" class="sr-only">Search articles</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Search titles" class="min-w-0 rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-500 focus:ring-stone-500">
                    <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Search</button>
                </form>
                <a href="{{ route('posts.create') }}" class="rounded-full bg-stone-900 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-stone-700">New article</a>
            </div>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        <div class="overflow-x-auto rounded-2xl border border-stone-200 bg-white">
            <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-wide text-stone-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 sm:px-5">Title</th>
                        <th scope="col" class="px-4 py-3 sm:px-5">Author</th>
                        <th scope="col" class="px-4 py-3 sm:px-5">Status</th>
                        <th scope="col" class="px-4 py-3 sm:px-5">Published</th>
                        <th scope="col" class="px-4 py-3 sm:px-5">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($posts as $post)
                        <tr>
                            <td class="max-w-xs px-4 py-4 sm:px-5">
                                <a href="{{ route('posts.edit', $post) }}" class="font-semibold text-stone-950 hover:underline">{{ $post->title }}</a>
                                <p class="mt-1 text-xs capitalize text-stone-500">{{ $post->type }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-stone-600 sm:px-5">{{ $post->user->name }}</td>
                            <td class="whitespace-nowrap px-4 py-4 sm:px-5">
                                @if ($post->published_at === null)
                                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-600">Draft</span>
                                @elseif ($post->published_at->isFuture())
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Scheduled</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">Published</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-stone-600 sm:px-5">{{ $post->published_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-4 sm:px-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('posts.edit', $post) }}" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-100">Edit</a>
                                    @if ($post->published_at !== null)
                                        <form method="POST" action="{{ route('admin.posts.unpublish', $post) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-full border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">Unpublish</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Delete this article or page?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-stone-600">No articles or pages matched your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$posts" />
    </section>
</x-admin-layout>

<x-admin-layout>
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="flex flex-col gap-3">
            <a href="{{ route('admin.dashboard') }}" class="w-fit text-sm font-medium text-stone-600 hover:text-stone-950">
                &larr; Admin dashboard
            </a>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Moderation</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">Pending comments</h1>
            <p class="text-stone-600">Approve comments to publish them or reject and delete them.</p>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <div class="flex flex-col gap-5">
            @forelse ($comments as $comment)
                <article class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                    <header class="flex flex-col gap-1">
                        <a href="{{ $comment->post->url() }}" class="font-semibold text-stone-950 hover:underline">
                            {{ $comment->post->title }}
                        </a>
                        <p class="text-sm text-stone-500">
                            By {{ $comment->author_name ?: $comment->user?->name ?: 'Anonymous' }}
                            @if ($comment->author_email)
                                · <span>{{ $comment->author_email }}</span>
                            @endif
                            · {{ $comment->created_at->diffForHumans() }}
                            @if ($comment->parent_id)
                                · Reply
                            @endif
                        </p>
                    </header>
                    <p class="whitespace-pre-line break-words text-sm leading-7 text-stone-700">{{ $comment->content }}</p>
                    <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-4">
                        <form method="POST" action="{{ route('admin.comments.approve', $comment) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                Reject and delete
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-stone-200 bg-white px-5 py-8 text-center text-sm text-stone-600">
                    There are no comments awaiting moderation.
                </p>
            @endforelse
        </div>

        <x-pagination :paginator="$comments" />
    </section>
</x-admin-layout>

@props(['blogSettings' => null])

<footer class="border-t border-stone-200 bg-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_minmax(0,1fr)] sm:items-start">
            <div class="flex max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="w-fit text-lg font-bold tracking-tight text-stone-950">{{ config('app.name', 'Laravel') }}</a>
                <p class="text-sm leading-6 text-stone-600">{{ $blogSettings?->site_description ?: 'Thoughtful stories, ideas, and guides from our community.' }}</p>
            </div>

            @if ($footerTags->isNotEmpty())
                <section aria-labelledby="footer-tags-title" class="flex flex-col gap-3 sm:items-center">
                    <h2 id="footer-tags-title" class="text-xs font-bold uppercase tracking-[0.14em] text-stone-500">Topik populer</h2>
                    <nav aria-label="Tag populer" class="flex flex-wrap gap-2 sm:justify-center">
                        @foreach ($footerTags as $tag)
                            <a
                                href="{{ route('tags.show', $tag->slug) }}"
                                aria-label="{{ $tag->name }}, {{ $tag->published_articles_count }} artikel"
                                @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-semibold transition hover:-translate-y-0.5 hover:bg-emerald-100 hover:text-emerald-950',
                                    'bg-emerald-50 text-base text-emerald-900' => $loop->first,
                                    'bg-stone-100 text-sm text-stone-700' => $loop->index > 0 && $loop->index < 4,
                                    'bg-stone-50 text-xs text-stone-600' => $loop->index >= 4,
                                ])
                            >
                                {{ $tag->name }}
                                <span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[10px] font-medium text-stone-500">{{ $tag->published_articles_count }}</span>
                            </a>
                        @endforeach
                    </nav>
                </section>
            @endif

            <nav aria-label="Footer navigation" class="flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium sm:justify-end">
                <a href="{{ route('home') }}" class="text-stone-600 hover:text-stone-950">Home</a>
                <a href="{{ route('posts.index') }}" class="text-stone-600 hover:text-stone-950">Articles</a>
                @foreach ($footerMenuItems as $menuItem)
                    <a href="{{ $menuItem->url }}" @if ($menuItem->is_external) target="_blank" rel="noopener noreferrer" @endif class="text-stone-600 hover:text-stone-950">{{ $menuItem->label }}</a>
                @endforeach
                @auth
                    <a href="{{ route('profile.edit') }}" class="text-stone-600 hover:text-stone-950">Profile</a>
                    @if (Auth::user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="text-stone-600 hover:text-stone-950">Admin</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-stone-600 hover:text-stone-950">Log in</a>
                @endauth
            </nav>
        </div>
        <p class="border-t border-stone-200 pt-5 text-xs text-stone-500">&copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
    </div>
</footer>

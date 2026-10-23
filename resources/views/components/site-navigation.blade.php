@props(['siteTitle' => config('app.name', 'Laravel')])

<header
    x-data="siteSearch(@js(route('search')))"
    @keydown.window.meta.k.prevent="open()"
    @keydown.window.ctrl.k.prevent="open()"
    class="border-b border-stone-200 bg-white"
>
    <nav data-auto-close-navigation aria-label="Main navigation" class="mx-auto flex min-h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="shrink-0 text-xl font-bold tracking-tight text-stone-950">
            {{ $siteTitle }}
        </a>

        <div class="hidden items-center gap-2 text-sm font-medium md:flex">
            <details class="group relative">
                <summary class="flex cursor-pointer list-none items-center gap-1 rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                    Articles
                    <svg aria-hidden="true" class="size-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                </summary>
                <div class="absolute left-0 top-11 z-20 flex min-w-52 flex-col gap-1 rounded-xl border border-stone-200 bg-white p-2 shadow-lg">
                    <a href="{{ route('posts.index') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Browse articles</a>
                    @auth
                        <a href="{{ route('posts.create') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Write an article</a>
                        @if (Auth::user()->is_admin)
                            <div class="my-1 border-t border-stone-100"></div>
                            <a href="{{ route('admin.categories.index') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Categories</a>
                            <a href="{{ route('admin.tags.index') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Tags</a>
                            <a href="{{ route('admin.media.index') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Media library</a>
                        @endif
                    @endauth
                </div>
            </details>

            @foreach ($headerMenuItems as $menuItem)
                <a href="{{ $menuItem->url }}" @if ($menuItem->is_external) target="_blank" rel="noopener noreferrer" @endif class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">{{ $menuItem->label }}</a>
            @endforeach

            @auth
                @if (Auth::user()->is_admin)
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1 rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                            Comments
                            <svg aria-hidden="true" class="size-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                        </summary>
                        <div class="absolute left-0 top-11 z-20 flex min-w-52 flex-col gap-1 rounded-xl border border-stone-200 bg-white p-2 shadow-lg">
                            <a href="{{ route('admin.comments.index') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Moderate comments</a>
                        </div>
                    </details>

                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1 rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                            Settings
                            <svg aria-hidden="true" class="size-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                        </summary>
                        <div class="absolute left-0 top-11 z-20 flex min-w-52 flex-col gap-1 rounded-xl border border-stone-200 bg-white p-2 shadow-lg">
                            <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Admin dashboard</a>
                            <a href="{{ route('admin.settings.seo.edit') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Blog settings</a>
                        </div>
                    </details>
                @endif

                <details class="group relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                        Profile
                        <svg aria-hidden="true" class="size-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                    </summary>
                    <div class="absolute right-0 top-11 z-20 flex min-w-52 flex-col gap-1 rounded-xl border border-stone-200 bg-white p-2 shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Profile settings</a>
                        <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-stone-700 hover:bg-stone-100">Log out</button>
                        </form>
                    </div>
                </details>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-stone-700 hover:bg-stone-100">Log in</a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="rounded-full bg-stone-900 px-4 py-2 text-white transition hover:bg-stone-700">Get started</a>
                @endif
            @endauth
            <button
                type="button"
                aria-label="Search the site"
                aria-controls="site-search-dialog"
                @click="open()"
                class="inline-flex size-10 items-center justify-center rounded-xl border border-stone-200 text-stone-600 transition hover:border-stone-300 hover:bg-stone-50 hover:text-stone-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
            >
                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="11" cy="11" r="7" stroke-width="1.8" />
                    <path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4" />
                </svg>
            </button>
        </div>

        <div class="flex items-center gap-2 md:hidden">
            <button
                type="button"
                aria-label="Search the site"
                aria-controls="site-search-dialog"
                @click="open()"
                class="inline-flex size-10 items-center justify-center rounded-lg border border-stone-200 text-stone-700 hover:bg-stone-100"
            >
                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="11" cy="11" r="7" stroke-width="1.8" />
                    <path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4" />
                </svg>
            </button>
            <details class="group relative">
                <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-lg border border-stone-200 text-stone-700 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                    <span class="sr-only">Toggle navigation</span>
                    <svg aria-hidden="true" class="size-5 group-open:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg aria-hidden="true" class="hidden size-5 group-open:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </summary>
                <div class="absolute right-0 top-12 z-20 flex max-h-[80vh] min-w-60 flex-col gap-1 overflow-y-auto rounded-xl border border-stone-200 bg-white p-2 shadow-lg">
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-stone-800 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                        Articles
                        <span aria-hidden="true" class="transition group-open:rotate-180">⌄</span>
                    </summary>
                    <div class="flex flex-col gap-1 pb-2 pl-3">
                        <a href="{{ route('posts.index') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Browse articles</a>
                        @auth
                            <a href="{{ route('posts.create') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Write an article</a>
                            @if (Auth::user()->is_admin)
                                <a href="{{ route('admin.categories.index') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Categories</a>
                                <a href="{{ route('admin.tags.index') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Tags</a>
                                <a href="{{ route('admin.media.index') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Media library</a>
                            @endif
                        @endauth
                    </div>
                </details>
                @foreach ($headerMenuItems as $menuItem)
                    <a href="{{ $menuItem->url }}" @if ($menuItem->is_external) target="_blank" rel="noopener noreferrer" @endif class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">{{ $menuItem->label }}</a>
                @endforeach
                @auth
                    @if (Auth::user()->is_admin)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-stone-800 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                                Comments
                                <span aria-hidden="true" class="transition group-open:rotate-180">⌄</span>
                            </summary>
                            <div class="pb-2 pl-3">
                                <a href="{{ route('admin.comments.index') }}" class="block rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Moderate comments</a>
                            </div>
                        </details>
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-stone-800 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                                Settings
                                <span aria-hidden="true" class="transition group-open:rotate-180">⌄</span>
                            </summary>
                            <div class="pb-2 pl-3">
                                <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Admin dashboard</a>
                                <a href="{{ route('admin.settings.seo.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Blog settings</a>
                            </div>
                        </details>
                    @endif
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-stone-800 hover:bg-stone-100 [&::-webkit-details-marker]:hidden">
                            Profile
                            <span aria-hidden="true" class="transition group-open:rotate-180">⌄</span>
                        </summary>
                        <div class="flex flex-col gap-1 pb-2 pl-3">
                            <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Profile settings</a>
                            <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Dashboard</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-stone-700 hover:bg-stone-100">Log out</button>
                            </form>
                        </div>
                    </details>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Log in</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Get started</a>
                    @endif
                @endauth
                </div>
            </details>
        </div>
    </nav>

    <div
        id="site-search-dialog"
        x-cloak
        x-show="isOpen"
        x-transition.opacity
        @click.self="close()"
        @keydown.escape.stop="close()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="site-search-title"
        class="fixed inset-0 z-50 overflow-y-auto bg-stone-950/60 px-4 pb-8 pt-[10vh] backdrop-blur-sm sm:pt-[14vh]"
    >
        <section class="mx-auto flex w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl shadow-stone-950/30">
            <div class="flex items-center gap-3 border-b border-stone-200 px-4 sm:px-5">
                <svg aria-hidden="true" class="size-5 shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="11" cy="11" r="7" stroke-width="1.8" />
                    <path stroke-linecap="round" stroke-width="1.8" d="m16 16 4 4" />
                </svg>
                <h2 id="site-search-title" class="sr-only">Search the site</h2>
                <input
                    x-ref="searchInput"
                    x-model="query"
                    @input="scheduleSearch()"
                    type="search"
                    autocomplete="off"
                    placeholder="Search articles and pages..."
                    aria-label="Search articles and pages"
                    class="h-16 min-w-0 flex-1 border-0 bg-transparent px-0 text-base text-stone-900 placeholder:text-stone-400 focus:ring-0 sm:text-lg"
                >
                <kbd class="hidden rounded-md border border-stone-200 bg-stone-50 px-2 py-1 text-xs font-medium text-stone-500 sm:inline">ESC</kbd>
                <button type="button" @click="close()" aria-label="Close search" class="rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900 sm:hidden">
                    <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="max-h-[60vh] min-h-24 overflow-y-auto p-3 sm:p-4">
                <p x-show="query.trim().length < 3" class="px-3 py-5 text-sm text-stone-500">
                    Type at least 3 characters to search.
                </p>

                <div x-show="loading" role="status" class="px-3 py-5 text-sm text-stone-500">
                    Searching...
                </div>

                <p x-show="error" x-text="error" role="alert" class="rounded-xl bg-red-50 px-3 py-3 text-sm text-red-700"></p>

                <template x-if="searched && results.length > 0">
                    <div class="flex flex-col gap-1">
                        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.14em] text-stone-400">Search results</p>
                        <template x-for="result in results" :key="result.url">
                            <a :href="result.url" class="group flex flex-col gap-1 rounded-xl px-3 py-3 transition hover:bg-stone-50 focus-visible:bg-stone-50 focus-visible:outline-none">
                                <div class="flex items-center justify-between gap-3">
                                    <span x-text="result.title" class="truncate font-semibold text-stone-900 group-hover:text-emerald-800"></span>
                                    <span class="flex shrink-0 items-center gap-2 text-xs text-stone-400">
                                        <span x-text="result.type"></span>
                                        <span aria-hidden="true">·</span>
                                        <span x-text="result.published_at"></span>
                                    </span>
                                </div>
                                <span x-show="result.excerpt" x-text="result.excerpt" class="line-clamp-2 text-sm leading-6 text-stone-500"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <p x-show="searched && results.length === 0" class="px-3 py-5 text-sm text-stone-500">
                    No matching articles or pages found.
                </p>
            </div>

            <footer class="flex items-center justify-between gap-3 border-t border-stone-200 bg-stone-50 px-4 py-3 text-xs text-stone-500 sm:px-5">
                <span>Search published articles and pages</span>
                <span class="hidden items-center gap-1 sm:flex"><kbd class="rounded border border-stone-200 bg-white px-1.5 py-0.5">Ctrl</kbd><span>or</span><kbd class="rounded border border-stone-200 bg-white px-1.5 py-0.5">⌘</kbd><span>+</span><kbd class="rounded border border-stone-200 bg-white px-1.5 py-0.5">K</kbd></span>
            </footer>
        </section>
    </div>
</header>

<x-app-layout>
    <div class="flex min-h-full flex-1 flex-col lg:flex-row">
        <aside class="border-b border-stone-200 bg-white lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r">
            <div class="flex flex-col gap-4 p-4 sm:p-6 lg:sticky lg:top-0 lg:min-h-full lg:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Ruang kerja</p>
                        <p class="mt-1 text-sm font-semibold text-stone-950">Administrasi</p>
                    </div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-100">
                        <svg data-menu-icon="view-site" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z" /><circle cx="12" cy="12" r="3" stroke-width="1.8" /></svg>
                        Lihat situs
                    </a>
                </div>
                <nav aria-label="Navigasi admin" class="-mx-1 flex gap-1 overflow-x-auto pb-1 lg:mx-0 lg:flex-col lg:overflow-visible">
                    <a href="{{ route('admin.dashboard') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.dashboard'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.dashboard')])>
                        <svg data-menu-icon="dashboard" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke-width="1.8" /><rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke-width="1.8" /><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke-width="1.8" /><rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke-width="1.8" /></svg>
                        Ringkasan
                    </a>
                    <p class="hidden px-3 pt-3 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-400 lg:block">Kelola</p>
                    <a href="{{ route('admin.posts.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.posts.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.posts.*')])>
                        <svg data-menu-icon="articles" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.5h7l4 4v13H7a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.5v5h5M9 13h7M9 16.5h7" /></svg>
                        Artikel
                    </a>
                    <a href="{{ route('admin.article-backups.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.article-backups.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.article-backups.*')])>
                        <svg data-menu-icon="backups" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3.5v11m0 0 4-4m-4 4-4-4M5 16v4h14v-4" /></svg>
                        Backup artikel
                    </a>
                    <a href="{{ route('admin.categories.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.categories.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.categories.*')])>
                        <svg data-menu-icon="categories" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.5 5.5h7l2 2h8v11h-17v-13Z" /></svg>
                        Kategori
                    </a>
                    <a href="{{ route('admin.tags.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.tags.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.tags.*')])>
                        <svg data-menu-icon="tags" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h8l8 8-7 7-8-8V5Z" /><circle cx="8" cy="9" r="1" fill="currentColor" stroke="none" /></svg>
                        Tag
                    </a>
                    <a href="{{ route('admin.comments.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.comments.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.comments.*')])>
                        <svg data-menu-icon="comments" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5.5h16v11H11l-5 3v-3H4v-11Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 13h5" /></svg>
                        Komentar
                    </a>
                    <a href="{{ route('admin.media.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.media.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.media.*')])>
                        <svg data-menu-icon="media" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3.5" y="4.5" width="17" height="15" rx="2" stroke-width="1.8" /><circle cx="9" cy="10" r="1.5" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 17 5-4 3 2 3-3 4 4" /></svg>
                        Pustaka media
                    </a>
                    <a href="{{ route('admin.menus.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.menus.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.menus.*')])>
                        <svg data-menu-icon="menus" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /><circle cx="7" cy="6" r="1" fill="currentColor" stroke="none" /><circle cx="7" cy="12" r="1" fill="currentColor" stroke="none" /><circle cx="7" cy="18" r="1" fill="currentColor" stroke="none" /></svg>
                        Menu blog
                    </a>
                    <a href="{{ route('admin.users.index') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.users.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.users.*')])>
                        <svg data-menu-icon="users" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="9" cy="8" r="3" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.5 20v-1a5.5 5.5 0 0 1 11 0v1h-11ZM16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 4.8V20" /></svg>
                        Pengguna
                    </a>
                    <p class="hidden px-3 pt-3 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-400 lg:block">Pengaturan</p>
                    <a href="{{ route('admin.settings.seo.edit') }}" @class(['inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.settings.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.settings.*')])>
                        <svg data-menu-icon="settings" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19.4 15 .1.1a1.8 1.8 0 1 1-2.5 2.5l-.1-.1a1.8 1.8 0 0 0-3 .9v.2a1.8 1.8 0 1 1-3.6 0v-.2a1.8 1.8 0 0 0-3-.9l-.1.1a1.8 1.8 0 1 1-2.5-2.5l.1-.1a1.8 1.8 0 0 0-.9-3H3.7a1.8 1.8 0 1 1 0-3.6h.2a1.8 1.8 0 0 0 .9-3l-.1-.1a1.8 1.8 0 1 1 2.5-2.5l.1.1a1.8 1.8 0 0 0 3-.9v-.2a1.8 1.8 0 1 1 3.6 0v.2a1.8 1.8 0 0 0 3 .9l.1-.1a1.8 1.8 0 1 1 2.5 2.5l-.1.1a1.8 1.8 0 0 0 .9 3h.2a1.8 1.8 0 1 1 0 3.6h-.2a1.8 1.8 0 0 0-.9 3Z" /></svg>
                        Pengaturan blog
                    </a>
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-100">
                        <svg data-menu-icon="profile" aria-hidden="true" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="8" r="3.5" stroke-width="1.8" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 20a7.5 7.5 0 0 1 15 0" /></svg>
                        Profil
                    </a>
                </nav>
            </div>
        </aside>
        <main class="min-w-0 flex-1">
            {{ $slot }}
        </main>
    </div>
</x-app-layout>

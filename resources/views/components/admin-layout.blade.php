<x-app-layout>
    <div class="flex min-h-full flex-1 flex-col lg:flex-row">
        <aside class="border-b border-stone-200 bg-white lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r">
            <div class="flex flex-col gap-4 p-4 sm:p-6 lg:sticky lg:top-0 lg:min-h-full lg:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Ruang kerja</p>
                        <p class="mt-1 text-sm font-semibold text-stone-950">Administrasi</p>
                    </div>
                    <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-100">Lihat situs</a>
                </div>
                <nav aria-label="Navigasi admin" class="-mx-1 flex gap-1 overflow-x-auto pb-1 lg:mx-0 lg:flex-col lg:overflow-visible">
                    <a href="{{ route('admin.dashboard') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.dashboard'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.dashboard')])>Ringkasan</a>
                    <p class="hidden px-3 pt-3 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-400 lg:block">Kelola</p>
                    <a href="{{ route('admin.posts.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.posts.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.posts.*')])>Artikel</a>
                    <a href="{{ route('admin.categories.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.categories.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.categories.*')])>Kategori</a>
                    <a href="{{ route('admin.tags.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.tags.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.tags.*')])>Tag</a>
                    <a href="{{ route('admin.comments.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.comments.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.comments.*')])>Komentar</a>
                    <a href="{{ route('admin.media.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.media.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.media.*')])>Pustaka media</a>
                    <a href="{{ route('admin.menus.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.menus.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.menus.*')])>Menu blog</a>
                    <a href="{{ route('admin.users.index') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.users.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.users.*')])>Pengguna</a>
                    <p class="hidden px-3 pt-3 text-[11px] font-bold uppercase tracking-[0.14em] text-stone-400 lg:block">Pengaturan</p>
                    <a href="{{ route('admin.settings.seo.edit') }}" @class(['whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-stone-900 text-white' => request()->routeIs('admin.settings.*'), 'text-stone-700 hover:bg-stone-100' => ! request()->routeIs('admin.settings.*')])>Pengaturan blog</a>
                    <a href="{{ route('profile.edit') }}" class="whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-100">Profil</a>
                </nav>
            </div>
        </aside>
        <main class="min-w-0 flex-1">
            {{ $slot }}
        </main>
    </div>
</x-app-layout>

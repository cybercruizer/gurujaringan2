<x-admin-layout>
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-7 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Site navigation</p>
                <h1 class="text-3xl font-bold tracking-tight text-stone-950">Blog menus</h1>
                <p class="text-stone-600">Manage the links shown in the public header and footer.</p>
            </div>
            <a href="{{ route('admin.menus.create') }}" class="w-fit rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white hover:bg-stone-700">Add menu link</a>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        <div class="flex flex-col gap-3">
            @forelse ($menuItems as $menuItem)
                <article class="flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-stone-950">{{ $menuItem->label }}</h2>
                            <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold uppercase text-stone-600">{{ $menuItem->location }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $menuItem->is_visible ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $menuItem->is_visible ? 'Visible' : 'Hidden' }}</span>
                        </div>
                        <p class="truncate text-sm text-stone-500">{{ $menuItem->url }} · Order {{ $menuItem->position }} · {{ $menuItem->is_external ? 'External' : 'Internal' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <a href="{{ route('admin.menus.edit', $menuItem) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Edit</a>
                        <form method="POST" action="{{ route('admin.menus.destroy', $menuItem) }}" onsubmit="return confirm('Delete this menu link?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-stone-300 bg-white px-5 py-10 text-center text-sm text-stone-600">No custom menu links yet. Built-in navigation remains available.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$menuItems" />
    </section>
</x-admin-layout>

<x-admin-layout>
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Access management</p>
                <h1 class="text-3xl font-bold tracking-tight text-stone-950">Users</h1>
                <p class="text-stone-600">Search accounts, manage administrator access, and remove eligible accounts.</p>
            </div>
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                <label for="search" class="sr-only">Search users</label>
                <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Name or email" class="min-w-0 rounded-xl border-stone-300 text-sm shadow-sm focus:border-stone-500 focus:ring-stone-500">
                <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Search</button>
            </form>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        <div class="flex flex-col gap-3">
            @forelse ($users as $user)
                <article class="flex flex-col gap-5 rounded-2xl border border-stone-200 bg-white p-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-stone-950">{{ $user->name }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_admin ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">{{ $user->is_admin ? 'Administrator' : 'Member' }}</span>
                            @if (auth()->user()->is($user))
                                <span class="text-xs font-medium text-stone-500">You</span>
                            @endif
                        </div>
                        <p class="text-sm text-stone-600">{{ $user->email }}</p>
                        <p class="text-xs text-stone-500">{{ $user->posts_count }} {{ \Illuminate\Support\Str::plural('post', $user->posts_count) }} · {{ $user->comments_count }} {{ \Illuminate\Support\Str::plural('comment', $user->comments_count) }}</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <label for="role-{{ $user->id }}" class="sr-only">Role for {{ $user->name }}</label>
                            <select id="role-{{ $user->id }}" name="role" class="rounded-xl border-stone-300 text-sm text-stone-800 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                                <option value="member" @selected(! $user->is_admin)>Member</option>
                                <option value="admin" @selected($user->is_admin)>Administrator</option>
                            </select>
                            <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100">Save role</button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user? Users with posts or comments cannot be deleted.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" @disabled(auth()->user()->is($user)) class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">Delete user</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-stone-300 bg-white px-5 py-10 text-center text-sm text-stone-600">No users matched your search.</p>
            @endforelse
        </div>

        <x-pagination :paginator="$users" />
    </section>
</x-admin-layout>

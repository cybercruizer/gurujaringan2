<x-admin-layout>
    <section class="mx-auto flex w-full max-w-3xl flex-col gap-7 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <header class="flex flex-col gap-2">
            <a href="{{ route('admin.menus.index') }}" class="w-fit text-sm font-medium text-stone-600 hover:text-stone-950">&larr; Back to blog menus</a>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Site navigation</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">{{ $menuItem->exists ? 'Edit menu link' : 'Add menu link' }}</h1>
        </header>

        <form method="POST" action="{{ $action }}" class="flex flex-col gap-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Please correct the following errors:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <label for="label" class="text-sm font-semibold text-stone-800">Link label</label>
                    <input id="label" name="label" value="{{ old('label', $menuItem->label) }}" required maxlength="100" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                </div>
                <div class="flex flex-col gap-2">
                    <label for="location" class="text-sm font-semibold text-stone-800">Menu location</label>
                    <select id="location" name="location" required class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                        <option value="header" @selected(old('location', $menuItem->location) === 'header')>Header</option>
                        <option value="footer" @selected(old('location', $menuItem->location) === 'footer')>Footer</option>
                    </select>
                </div>
            </div>

            <fieldset class="flex flex-col gap-3">
                <legend class="text-sm font-semibold text-stone-800">Link type</legend>
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input type="radio" name="is_external" value="0" @checked(old('is_external', (int) $menuItem->is_external) == 0) class="border-stone-300 text-stone-900 focus:ring-stone-500">
                    Internal path (for example, <code>/posts</code>)
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input type="radio" name="is_external" value="1" @checked(old('is_external', (int) $menuItem->is_external) == 1) class="border-stone-300 text-stone-900 focus:ring-stone-500">
                    External HTTP(S) URL
                </label>
                @error('is_external')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="flex flex-col gap-2">
                <label for="url" class="text-sm font-semibold text-stone-800">URL</label>
                <input id="url" name="url" value="{{ old('url', $menuItem->url) }}" required maxlength="2048" placeholder="/posts or https://example.com" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                @error('url')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <label for="position" class="text-sm font-semibold text-stone-800">Order</label>
                    <input id="position" name="position" type="number" min="0" max="65535" value="{{ old('position', $menuItem->position) }}" required class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                </div>
                <label class="flex items-center gap-3 self-end pb-3 text-sm text-stone-700">
                    <input type="hidden" name="is_visible" value="0">
                    <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $menuItem->is_visible)) class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                    Show this link publicly
                </label>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-stone-200 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.menus.index') }}" class="rounded-full border border-stone-300 px-5 py-3 text-center text-sm font-semibold text-stone-700 hover:bg-stone-100">Cancel</a>
                <button type="submit" class="rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white hover:bg-stone-700">{{ $menuItem->exists ? 'Save changes' : 'Create link' }}</button>
            </div>
        </form>
    </section>
</x-admin-layout>

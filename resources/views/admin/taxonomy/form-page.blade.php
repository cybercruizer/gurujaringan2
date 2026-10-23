<x-admin-layout>
    <section class="mx-auto flex w-full max-w-2xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14">
        <header class="flex flex-col gap-2">
            <a href="{{ $cancelUrl }}" class="w-fit text-sm font-medium text-stone-600 hover:text-stone-950">&larr; Back to management</a>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Article management</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">{{ $title }}</h1>
            <p class="text-stone-600">{{ $description }}</p>
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

            <div class="flex flex-col gap-2">
                <label for="name" class="text-sm font-semibold text-stone-800">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $item->name) }}" required maxlength="255" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                @error('name')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            @if ($hasDescription)
                <div class="flex flex-col gap-2">
                    <label for="description" class="text-sm font-semibold text-stone-800">Description <span class="font-normal text-stone-500">(optional)</span></label>
                    <textarea id="description" name="description" rows="4" maxlength="2000" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('description', $item->description) }}</textarea>
                    @error('description')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex flex-col-reverse gap-3 border-t border-stone-200 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ $cancelUrl }}" class="rounded-full border border-stone-300 px-5 py-3 text-center text-sm font-semibold text-stone-700 hover:bg-stone-100">Cancel</a>
                <button type="submit" class="rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white hover:bg-stone-700">{{ $item->exists ? 'Save changes' : 'Create' }}</button>
            </div>
        </form>
    </section>
</x-admin-layout>

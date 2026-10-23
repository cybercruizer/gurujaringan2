<x-app-layout>
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14">
        <div class="flex flex-col gap-2">
            <a href="{{ route('posts.index') }}" class="w-fit text-sm font-medium text-stone-600 hover:text-stone-950">&larr; All articles</a>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">Write a post</h1>
        </div>

        @include('posts.partials.form', [
            'action' => route('posts.store'),
            'method' => 'POST',
            'submitLabel' => 'Publish post',
        ])
    </section>
</x-app-layout>

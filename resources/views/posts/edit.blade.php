<x-app-layout>
    <section class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14">
        <div class="flex flex-col gap-2">
            <a href="{{ $post->url() }}" class="w-fit text-sm font-medium text-stone-600 hover:text-stone-950">&larr; Back to post</a>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">Edit post</h1>
        </div>

        @include('posts.partials.form', [
            'action' => route('posts.update', $post),
            'method' => 'PUT',
            'submitLabel' => 'Save changes',
        ])
    </section>
</x-app-layout>

@props(['post'])

<article {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-2xl border border-[#D5DBEF] bg-white transition duration-200 hover:-translate-y-0.5 hover:border-[#FFB84D] hover:shadow-[0_18px_40px_-18px_rgba(11,16,32,.35)] focus-within:border-[#FFB84D] motion-reduce:transition-none motion-reduce:hover:translate-y-0']) }}>
    @if ($post->featured_image)
        <div class="relative aspect-[16/10] w-full overflow-hidden bg-[#0B1020]">
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image_thumbnail ?: $post->featured_image) }}"
                alt=""
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03] motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                loading="lazy"
            >
            <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-[#0B1020]/40 to-transparent"></div>
        </div>
    @else
        {{-- Keeps card heights consistent when a post has no image --}}
        <div aria-hidden="true" class="relative aspect-[16/10] w-full overflow-hidden bg-[#0B1020]">
            <div class="absolute inset-0 opacity-60 [background-image:linear-gradient(to_right,rgba(139,150,184,.18)_1px,transparent_1px),linear-gradient(to_bottom,rgba(139,150,184,.18)_1px,transparent_1px)] [background-size:28px_28px]"></div>
            <div class="absolute left-1/2 top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#FFB84D] shadow-[0_0_0_8px_rgba(255,184,77,.15),0_0_0_20px_rgba(255,184,77,.07)]"></div>
        </div>
    @endif

    <div class="flex flex-1 flex-col gap-3 p-5 sm:p-6">
        @if ($post->type === 'page')
            <span class="w-fit rounded-md border border-[#B9C2E0] bg-[#EEF1FA] px-2 py-0.5 text-xs font-semibold text-[#3B4670]">
                Page
            </span>
        @endif

        <h2 class="text-xl font-bold leading-snug tracking-[-0.02em] text-[#0B1020]">
            {{-- The ::after stretches the link over the whole card --}}
            <a href="{{ $post->url() }}" class="outline-none after:absolute after:inset-0 after:content-[''] focus-visible:underline focus-visible:decoration-[#FFB84D] focus-visible:decoration-2 focus-visible:underline-offset-4">
                {{ $post->title }}
            </a>
        </h2>

        @if ($post->excerpt)
            <p class="line-clamp-3 leading-7 text-[#5A6482]">{{ $post->excerpt }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between gap-4 border-t border-[#E3E8F6] pt-4 text-sm text-[#5A6482]">
            <span class="truncate">By <span class="font-semibold text-[#0B1020]">{{ $post->user->name }}</span></span>

            @if ($post->published_at)
                @php($publishedAt = \Illuminate\Support\Carbon::parse($post->published_at))
                <time datetime="{{ $publishedAt->toDateString() }}" class="shrink-0 tabular-nums">
                    {{ $publishedAt->format('M j, Y') }}
                </time>
            @endif
        </div>
    </div>
</article>

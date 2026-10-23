<x-app-layout
    :seo-title="$post->seo_title ?: $post->title"
    :seo-description="$post->seo_description ?: $post->excerpt"
    :seo-image="$post->featured_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) : null"
    :seo-canonical="$post->url()"
    :seo-type="$post->type === 'page' ? 'website' : 'article'"
    :seo-noindex="$post->seo_noindex || ! $isPublished"
>
    @php
        $wordCount = count(preg_split('/\s+/u', trim(strip_tags((string) $content)), -1, PREG_SPLIT_NO_EMPTY));
        $readingMinutes = max(1, (int) ceil($wordCount / 200));
        $authorInitial = mb_strtoupper(mb_substr($post->user->name, 0, 1));
        $commentTotal = method_exists($comments, 'total') ? $comments->total() : count($comments);
        $canComment = $post->comments_enabled && $post->published_at && $post->published_at->lessThanOrEqualTo(now());
    @endphp

    <div class="bg-[#EEF1FA] font-[Schibsted_Grotesk,ui-sans-serif,system-ui,sans-serif]">
        {{-- ============ ARTICLE HEADER ============ --}}
        <header class="relative isolate overflow-hidden bg-[#0B1020] text-[#E8ECFA] [clip-path:polygon(0_0,100%_0,100%_calc(100%-32px),0_100%)] sm:[clip-path:polygon(0_0,100%_0,100%_calc(100%-56px),0_100%)]">
            <div aria-hidden="true" class="absolute inset-0 -z-10 opacity-70 [background-image:linear-gradient(to_right,rgba(139,150,184,.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(139,150,184,.12)_1px,transparent_1px)] [background-size:44px_44px] [mask-image:radial-gradient(ellipse_70%_90%_at_30%_30%,#000_25%,transparent_100%)]"></div>
            <div aria-hidden="true" class="absolute -right-20 -top-20 -z-10 h-80 w-80 rounded-full bg-[#4B3FD6]/25 blur-[100px]"></div>

            <div class="mx-auto flex w-full max-w-[62rem] flex-col items-start gap-6 px-4 pb-20 pt-8 sm:px-6 sm:pb-28 sm:pt-10 lg:px-8">
                <a href="{{ route('posts.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg py-1 text-sm font-medium text-[#A9B3D1] transition hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#FFB84D]">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                    All articles
                </a>

                @if ($post->type === 'page' || $post->categories->isNotEmpty() || ! $post->published_at)
                    <div class="flex flex-wrap items-center gap-2">
                        @unless ($post->published_at)
                            <span class="rounded-md bg-[#FFB84D] px-2.5 py-1 text-xs font-bold text-[#0B1020]">Draft</span>
                        @endunless
                        @if ($post->type === 'page')
                            <span class="rounded-md border border-white/25 px-2.5 py-1 text-xs font-semibold text-white">Page</span>
                        @endif
                        @foreach ($post->categories as $category)
                            <a href="{{ route('categories.show', $category->slug) }}" class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-semibold text-[#E8ECFA] ring-1 ring-inset ring-white/15 hover:bg-white/20">{{ $category->name }}</a>
                        @endforeach
                    </div>
                @endif

                <h1 class="max-w-3xl text-balance text-4xl font-extrabold leading-[1.08] tracking-[-0.03em] text-white sm:text-5xl lg:text-6xl">
                    {{ $post->title }}
                </h1>

                @if ($post->excerpt)
                    <p class="max-w-2xl text-lg leading-8 text-[#A9B3D1] sm:text-xl sm:leading-9">{{ $post->excerpt }}</p>
                @endif

                <div class="flex flex-wrap items-center gap-x-5 gap-y-3 pt-1">
                    <div class="flex items-center gap-3">
                        <span aria-hidden="true" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#FFB84D] text-base font-bold text-[#0B1020]">{{ $authorInitial }}</span>
                        <div class="flex flex-col leading-tight">
                            <span class="text-sm font-semibold text-white">{{ $post->user->name }}</span>
                            @if ($post->published_at)
                                <time datetime="{{ $post->published_at->toDateString() }}" class="text-sm text-[#8B96B8]">{{ $post->formatted_published_at }}</time>
                            @else
                                <span class="text-sm text-[#8B96B8]">Not published yet</span>
                            @endif
                        </div>
                    </div>
                    <p class="rounded-full bg-white/10 px-3 py-1.5 text-sm font-medium text-[#C9D1EA] ring-1 ring-inset ring-white/10">
                        {{ $readingMinutes }} min read
                    </p>
                </div>
            </div>
        </header>

        {{-- ============ ARTICLE BODY ============ --}}
        <div class="mx-auto grid w-full max-w-[62rem] grid-cols-1 gap-10 px-4 pb-14 pt-4 sm:px-6 sm:pb-20 lg:grid-cols-[minmax(0,1fr)_15rem] lg:gap-12 lg:px-8">
            <article class="flex w-full max-w-[44rem] flex-col gap-8">
                @if (session('status'))
                    <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </p>
                @endif

                {{-- Table of contents on small screens: collapsed so it never pushes the story down --}}
                @if ($headings !== [])
                    <details class="group rounded-2xl border border-[#D5DBEF] bg-white lg:hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-bold text-[#0B1020] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D] [&::-webkit-details-marker]:hidden">
                            In this article
                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-[#5A6482] transition group-open:rotate-180"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                        </summary>
                        <ol class="flex flex-col gap-1 border-t border-[#E3E8F6] px-5 py-4">
                            @foreach ($headings as $heading)
                                <li class="{{ $heading['level'] > 2 ? 'pl-4' : '' }}">
                                    <a href="#{{ $heading['id'] }}" class="block py-1 text-sm leading-6 text-[#3B4670] hover:text-[#0B1020]">{{ $heading['title'] }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif

                @if ($post->featured_image)
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                        alt="{{ $post->featuredMedia?->alt_text ?: $post->title }}"
                        class="max-h-[30rem] w-full rounded-2xl border border-[#D5DBEF] object-cover shadow-[0_24px_50px_-28px_rgba(11,16,32,.5)]"
                    >
                @endif

                <div class="rich-content text-[1.0625rem] leading-[1.85] text-[#1B2240] [&_h2]:scroll-mt-24 [&_h3]:scroll-mt-24 [&_h4]:scroll-mt-24">{!! $content !!}</div>

                @if ($post->tags->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-2 border-t border-[#D5DBEF] pt-6">
                        <span class="mr-1 text-sm font-semibold text-[#0B1020]">Tagged</span>
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('tags.show', $tag->slug) }}" class="rounded-full border border-[#B9C2E0] bg-white px-3 py-1 text-sm text-[#3B4670] hover:border-[#3B4670]">#{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                @if ($isPublished)
                    @php
                        $shareUrl = urlencode($post->url());
                        $shareTitle = urlencode($post->title);
                    @endphp
                    <section aria-labelledby="share-post-title" class="flex flex-col gap-3 border-t border-[#D5DBEF] pt-6">
                        <h2 id="share-post-title" class="text-sm font-bold text-[#0B1020]">Bagikan artikel ini</h2>
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Bagikan ke WhatsApp"
                                class="inline-flex items-center gap-2 rounded-xl border border-[#D5DBEF] bg-white px-4 py-2.5 text-sm font-semibold text-[#1B2240] transition hover:border-emerald-300 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D]"
                            >
                                <span aria-hidden="true" class="flex size-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">W</span>
                                WhatsApp
                            </a>
                            <a
                                href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Bagikan ke Facebook"
                                class="inline-flex items-center gap-2 rounded-xl border border-[#D5DBEF] bg-white px-4 py-2.5 text-sm font-semibold text-[#1B2240] transition hover:border-blue-300 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D]"
                            >
                                <span aria-hidden="true" class="flex size-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-800">f</span>
                                Facebook
                            </a>
                            <a
                                href="https://x.com/intent/post?url={{ $shareUrl }}&amp;text={{ $shareTitle }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Bagikan ke X"
                                class="inline-flex items-center gap-2 rounded-xl border border-[#D5DBEF] bg-white px-4 py-2.5 text-sm font-semibold text-[#1B2240] transition hover:border-stone-400 hover:bg-stone-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D]"
                            >
                                <span aria-hidden="true" class="flex size-6 items-center justify-center rounded-full bg-stone-200 text-xs font-bold text-stone-900">X</span>
                                X
                            </a>
                            <a
                                href="https://t.me/share/url?url={{ $shareUrl }}&amp;text={{ $shareTitle }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Bagikan ke Telegram"
                                class="inline-flex items-center gap-2 rounded-xl border border-[#D5DBEF] bg-white px-4 py-2.5 text-sm font-semibold text-[#1B2240] transition hover:border-sky-300 hover:bg-sky-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D]"
                            >
                                <span aria-hidden="true" class="flex size-6 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-800">T</span>
                                Telegram
                            </a>
                            <a
                                href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Bagikan ke LinkedIn"
                                class="inline-flex items-center gap-2 rounded-xl border border-[#D5DBEF] bg-white px-4 py-2.5 text-sm font-semibold text-[#1B2240] transition hover:border-sky-300 hover:bg-sky-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D]"
                            >
                                <span aria-hidden="true" class="flex size-6 items-center justify-center rounded-full bg-sky-100 text-[10px] font-bold text-sky-800">in</span>
                                LinkedIn
                            </a>
                        </div>
                    </section>
                @endif

                @can('update', $post)
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#D5DBEF] bg-white p-4 sm:p-5">
                        <p class="text-sm text-[#5A6482]">You can manage this post.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('posts.edit', $post) }}"
                               class="rounded-xl bg-[#0B1020] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1B2547] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFB84D]">
                                Edit post
                            </a>
                            <form method="POST" action="{{ route('posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">
                                    Delete post
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan
            </article>

            {{-- Table of contents on large screens: sticky beside the article --}}
            @if ($headings !== [])
                <aside class="hidden lg:block">
                    <nav aria-label="Table of contents" class="sticky top-8 flex flex-col gap-4">
                        <h2 class="text-base font-bold text-[#0B1020]">In this article</h2>
                        <ol class="flex flex-col gap-1 border-l-2 border-[#D5DBEF]">
                            @foreach ($headings as $heading)
                                <li class="{{ $heading['level'] > 2 ? 'pl-7' : 'pl-4' }}">
                                    <a href="#{{ $heading['id'] }}"
                                       class="-ml-[2px] block border-l-2 border-transparent py-1 pl-0 text-sm leading-6 text-[#5A6482] transition hover:text-[#0B1020] focus-visible:text-[#0B1020] focus-visible:outline-none">
                                        {{ $heading['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </aside>
            @endif
        </div>

        {{-- ============ COMMENTS ============ --}}
        <section id="comments" class="scroll-mt-6 border-t border-[#D5DBEF] bg-white">
            <div class="mx-auto flex w-full max-w-[62rem] flex-col gap-8 px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                <div class="flex max-w-[44rem] flex-col gap-2">
                    <h2 class="flex items-center gap-3 text-2xl font-extrabold tracking-[-0.02em] text-[#0B1020] sm:text-3xl">
                        Comments
                        <span class="rounded-full bg-[#EEF1FA] px-3 py-1 text-sm font-semibold tabular-nums text-[#3B4670]">{{ number_format($commentTotal) }}</span>
                    </h2>
                    <p class="text-base text-[#5A6482]">
                        @if ($canComment)
                            Join the conversation. Your comment appears once it has been reviewed.
                        @else
                            Comments are closed for this {{ $post->type === 'page' ? 'page' : 'post' }}.
                        @endif
                    </p>
                </div>

                <div class="flex max-w-[44rem] flex-col gap-8">
                    @if ($canComment)
                        <x-comment-form :post="$post" :captcha="$commentCaptchas['root']" />
                    @endif

                    <div class="flex flex-col gap-6">
                        @forelse ($comments as $comment)
                            <x-comment-thread :comment="$comment" :post="$post" :comment-captchas="$commentCaptchas" />
                        @empty
                            <div class="rounded-2xl border border-dashed border-[#B9C2E0] px-6 py-10 text-center">
                                <p class="text-base font-bold text-[#0B1020]">No comments yet.</p>
                                <p class="mt-1 text-sm text-[#5A6482]">Be the first to share your thoughts.</p>
                            </div>
                        @endforelse
                    </div>

                    <x-pagination :paginator="$comments" />
                </div>
            </div>
        </section>
    </div>
</x-app-layout>

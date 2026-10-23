<x-app-layout>
    {{-- One typeface, many weights. Remove these three lines if your layout already loads fonts. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        .hx-font { font-family: 'Schibsted Grotesk', ui-sans-serif, system-ui, sans-serif; }

        /* Blueprint grid that fades out toward the edges */
        .hx-grid {
            background-image:
                linear-gradient(to right, rgba(139, 150, 184, .14) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(139, 150, 184, .14) 1px, transparent 1px);
            background-size: 44px 44px;
            -webkit-mask-image: radial-gradient(ellipse 70% 80% at 70% 40%, #000 30%, transparent 100%);
            mask-image: radial-gradient(ellipse 70% 80% at 70% 40%, #000 30%, transparent 100%);
        }

        /* The one orchestrated moment: the network draws itself on page load */
        .hx-line {
            stroke-dasharray: 1;
            stroke-dashoffset: 1;
            animation: hx-draw 1.6s cubic-bezier(.65, 0, .35, 1) forwards;
        }
        .hx-line:nth-of-type(2) { animation-delay: .15s; }
        .hx-line:nth-of-type(3) { animation-delay: .3s; }
        .hx-line:nth-of-type(4) { animation-delay: .45s; }
        .hx-line:nth-of-type(5) { animation-delay: .6s; }
        .hx-line:nth-of-type(6) { animation-delay: .75s; }
        .hx-line:nth-of-type(7) { animation-delay: .9s; }

        .hx-node {
            opacity: 0;
            animation: hx-fade .5s ease-out forwards;
            animation-delay: 1.2s;
        }
        .hx-core-ring {
            transform-box: fill-box;
            transform-origin: center;
            animation: hx-ping 2.8s ease-out 1.8s infinite;
        }

        @keyframes hx-draw { to { stroke-dashoffset: 0; } }
        @keyframes hx-fade { to { opacity: 1; } }
        @keyframes hx-ping {
            0%   { transform: scale(1);   opacity: .55; }
            100% { transform: scale(3.2); opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .hx-line { animation: none; stroke-dashoffset: 0; }
            .hx-node { animation: none; opacity: 1; }
            .hx-core-ring { animation: none; opacity: 0; }
        }
    </style>

    @php
        $hasPosts = isset($posts) && count($posts);
        $postTotal = $hasPosts
            ? (method_exists($posts, 'total') ? $posts->total() : count($posts))
            : 0;
        $heroEyebrow = $blogSettings?->hero_eyebrow ?: 'Publishing new articles every week';
        $heroTitle = $blogSettings?->hero_title ?: 'Clear thinking about technology, from the people building it.';
        $heroDescription = $blogSettings?->hero_description ?: 'Practical articles, field notes, and lessons learned from our community of makers.';
        $heroCtaLabel = $blogSettings?->hero_cta_label ?: 'Read the latest articles';
    @endphp

    <div class="hx-font bg-[#EEF1FA]">
        {{-- ============ HERO ============ --}}
        <section class="relative isolate overflow-hidden bg-[#0B1020] text-[#E8ECFA] [clip-path:polygon(0_0,100%_0,100%_calc(100%-40px),0_100%)] sm:[clip-path:polygon(0_0,100%_0,100%_calc(100%-72px),0_100%)]">
            <div aria-hidden="true" class="hx-grid absolute inset-0 -z-10"></div>
            <div aria-hidden="true" class="absolute -right-24 top-0 -z-10 h-[28rem] w-[28rem] rounded-full bg-[#4B3FD6]/25 blur-[110px]"></div>

            <div class="mx-auto grid w-full max-w-7xl items-center gap-10 px-4 pb-28 pt-16 sm:px-6 sm:pb-40 sm:pt-24 lg:grid-cols-[1.15fr_0.85fr] lg:px-8">
                <div class="flex flex-col items-start gap-7">
                    <p class="flex items-center gap-2.5 text-sm font-medium text-[#8B96B8]">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-[#FFB84D] opacity-60 motion-safe:animate-ping"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-[#FFB84D]"></span>
                        </span>
                        {{ $heroEyebrow }}
                    </p>

                    <h1 class="max-w-3xl text-[2.5rem] font-extrabold leading-[1.02] tracking-[-0.035em] text-white sm:text-6xl lg:text-7xl">
                        {{ $heroTitle }}
                    </h1>

                    <p class="max-w-xl text-lg leading-8 text-[#A9B3D1]">
                        {{ $heroDescription }}
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="#posts"
                           class="inline-flex items-center rounded-xl bg-[#FFB84D] px-6 py-3.5 text-sm font-bold text-[#0B1020] shadow-[0_0_0_1px_rgba(255,184,77,.4),0_12px_40px_-8px_rgba(255,184,77,.55)] transition hover:bg-[#FFC970] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#FFB84D]">
                            {{ $heroCtaLabel }}
                        </a>
                        @if ($hasPosts)
                            <p class="text-sm text-[#8B96B8]">
                                <span class="font-semibold text-white">{{ number_format($postTotal) }}</span>
                                {{ \Illuminate\Support\Str::plural('article', $postTotal) }} published
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Self-drawing network: the single memorable element --}}
                <div aria-hidden="true" class="relative mx-auto hidden w-full max-w-md lg:block">
                    <svg viewBox="0 0 480 440" class="h-auto w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g stroke="#5B6BC0" stroke-width="1.5" stroke-linecap="round">
                            <path class="hx-line" pathLength="1" d="M240 220 L96 86" />
                            <path class="hx-line" pathLength="1" d="M240 220 L388 70" />
                            <path class="hx-line" pathLength="1" d="M240 220 L430 250" />
                            <path class="hx-line" pathLength="1" d="M240 220 L330 390" />
                            <path class="hx-line" pathLength="1" d="M240 220 L110 340" />
                            <path class="hx-line" pathLength="1" d="M96 86 L388 70" stroke-opacity=".45" />
                            <path class="hx-line" pathLength="1" d="M110 340 L330 390 L430 250" stroke-opacity=".45" />
                        </g>

                        <circle cx="240" cy="220" r="104" stroke="#26325A" stroke-width="1" stroke-dasharray="3 7" />
                        <circle cx="240" cy="220" r="168" stroke="#1B2547" stroke-width="1" />

                        <g class="hx-node" fill="#121A33" stroke="#7C8BE0" stroke-width="1.5">
                            <circle cx="96" cy="86" r="9" />
                            <circle cx="388" cy="70" r="12" />
                            <circle cx="430" cy="250" r="8" />
                            <circle cx="330" cy="390" r="10" />
                            <circle cx="110" cy="340" r="7" />
                        </g>

                        <circle class="hx-core-ring" cx="240" cy="220" r="14" fill="#FFB84D" />
                        <circle class="hx-node" cx="240" cy="220" r="14" fill="#FFB84D" />
                        <circle class="hx-node" cx="240" cy="220" r="5" fill="#0B1020" />
                    </svg>
                </div>
            </div>
        </section>

        {{-- ============ POSTS ============ --}}
        <section id="posts" class="mx-auto flex w-full max-w-7xl scroll-mt-6 flex-col gap-10 px-4 pb-20 pt-6 sm:px-6 sm:pb-28 lg:px-8">
            <div class="flex items-end justify-between gap-6 border-l-4 border-[#FFB84D] pl-5">
                <div class="flex flex-col gap-1.5">
                    <h2 class="text-3xl font-extrabold tracking-[-0.025em] text-[#0B1020] sm:text-4xl">Latest articles</h2>
                    <p class="text-base text-[#5A6482]">Newest first, from across the community.</p>
                </div>
                @if ($hasPosts)
                    <p class="hidden text-sm font-medium tabular-nums text-[#5A6482] sm:block">
                        {{ number_format($postTotal) }} {{ \Illuminate\Support\Str::plural('article', $postTotal) }}
                    </p>
                @endif
            </div>

            @if ($hasPosts)
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>

                @if (method_exists($posts, 'hasPages') && $posts->hasPages())
                    <x-pagination :paginator="$posts" />
                @endif
            @else
                <div class="rounded-2xl border border-dashed border-[#B9C2E0] bg-white px-6 py-16 text-center">
                    <p class="text-lg font-bold text-[#0B1020]">No articles published yet.</p>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-[#5A6482]">New articles will appear here as soon as they go live.</p>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

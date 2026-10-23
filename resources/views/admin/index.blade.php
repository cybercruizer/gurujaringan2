<x-admin-layout>
    <section class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Administration</p>
        <h1 class="text-3xl font-bold tracking-tight text-stone-950">Admin dashboard</h1>
        <p class="max-w-2xl leading-7 text-stone-600">Blog performance and shortcuts for managing your site.</p>

        <section aria-labelledby="metrics-heading" class="mt-5 flex flex-col gap-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-1">
                    <h2 id="metrics-heading" class="text-xl font-bold tracking-tight text-stone-950">Blog metrics</h2>
                    <p class="text-sm text-stone-500">Views count once per post per browser session. Referrals store external domains only.</p>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-end gap-2">
                    <div class="flex flex-col gap-1">
                        <label for="period" class="text-xs font-semibold uppercase tracking-wide text-stone-500">Date range</label>
                        <select id="period" name="period" class="rounded-xl border-stone-300 text-sm text-stone-800 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                            @foreach ($periods as $value => $label)
                                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('period')
                            <span class="text-xs text-red-700">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-700">Apply</button>
                </form>
            </div>

            <section aria-labelledby="views-chart-heading" class="flex flex-col gap-5 rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h3 id="views-chart-heading" class="font-semibold text-stone-950">Post views</h3>
                        <p class="text-sm text-stone-500">{{ $periods[$period] }} · {{ number_format($metrics['total_views']) }} total</p>
                    </div>
                    <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600">{{ $period === 'today' ? 'Hourly' : (in_array($period, ['last_7_days', 'this_month'], true) ? 'Daily' : ($period === 'this_year' ? 'Monthly' : 'Yearly')) }}</span>
                </div>

                <div role="img" aria-label="Post views {{ strtolower($periods[$period]) }} shown as a bar chart" class="overflow-x-auto">
                    <div class="flex h-56 min-w-max items-end gap-2 border-b border-l border-stone-200 px-3 pb-0 pt-3 sm:gap-3">
                        @foreach ($chart['values'] as $index => $value)
                            @php
                                $barHeight = $value > 0 && $chart['max'] > 0
                                    ? max(4, (int) round($value / $chart['max'] * 100))
                                    : 0;
                            @endphp
                            <div class="flex h-full w-8 flex-col items-center justify-end gap-2 sm:w-10" title="{{ $chart['labels'][$index] }}: {{ number_format($value) }} views">
                                <span class="text-[10px] font-semibold tabular-nums text-stone-500">{{ $value > 0 ? number_format($value) : '' }}</span>
                                <div class="w-full rounded-t-md bg-emerald-600 transition hover:bg-emerald-500" style="height: {{ $barHeight }}%"></div>
                                <span class="h-5 whitespace-nowrap text-[10px] text-stone-500">{{ $chart['labels'][$index] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <ul class="sr-only">
                    @foreach ($chart['values'] as $index => $value)
                        <li>{{ $chart['labels'][$index] }}: {{ $value }} views</li>
                    @endforeach
                </ul>
            </section>

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5">
                    <dt class="text-sm font-medium text-stone-500">Post views</dt>
                    <dd class="text-2xl font-bold tabular-nums text-stone-950">{{ number_format($metrics['total_views']) }}</dd>
                </div>
                <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5">
                    <dt class="text-sm font-medium text-stone-500">Published articles</dt>
                    <dd class="text-xs text-stone-400">Current</dd>
                    <dd class="text-2xl font-bold tabular-nums text-stone-950">{{ number_format($metrics['published_articles']) }}</dd>
                </div>
                <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5">
                    <dt class="text-sm font-medium text-stone-500">Published pages</dt>
                    <dd class="text-xs text-stone-400">Current</dd>
                    <dd class="text-2xl font-bold tabular-nums text-stone-950">{{ number_format($metrics['published_pages']) }}</dd>
                </div>
                <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5">
                    <dt class="text-sm font-medium text-stone-500">Approved comments</dt>
                    <dd class="text-2xl font-bold tabular-nums text-stone-950">{{ number_format($metrics['approved_comments']) }}</dd>
                </div>
                <div class="flex flex-col gap-1 rounded-2xl border border-stone-200 bg-white p-5">
                    <dt class="text-sm font-medium text-stone-500">Awaiting moderation</dt>
                    <dd class="text-2xl font-bold tabular-nums text-stone-950">{{ number_format($metrics['pending_comments']) }}</dd>
                </div>
            </dl>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section aria-labelledby="top-content-heading" class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 id="top-content-heading" class="font-semibold text-stone-950">Top content</h3>
                        <span class="text-xs font-medium uppercase tracking-wide text-stone-500">By views</span>
                    </div>
                    @forelse ($topContent as $item)
                        <a href="{{ $item->post->url() }}" class="flex items-center justify-between gap-4 rounded-xl px-3 py-2 transition hover:bg-stone-50">
                            <span class="min-w-0 truncate text-sm font-medium text-stone-700">{{ $item->post->title }}</span>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-stone-950">{{ number_format($item->view_count) }}</span>
                        </a>
                    @empty
                        <p class="rounded-xl bg-stone-50 px-3 py-4 text-sm text-stone-500">No published content views recorded yet.</p>
                    @endforelse
                </section>

                <section aria-labelledby="top-referrals-heading" class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 id="top-referrals-heading" class="font-semibold text-stone-950">Top referrals</h3>
                        <span class="text-xs font-medium uppercase tracking-wide text-stone-500">External domains</span>
                    </div>
                    @forelse ($topReferrals as $referral)
                        <div class="flex items-center justify-between gap-4 rounded-xl px-3 py-2">
                            <span class="min-w-0 truncate text-sm font-medium text-stone-700">{{ $referral->referrer_domain }}</span>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-stone-950">{{ number_format($referral->referral_count) }}</span>
                        </div>
                    @empty
                        <p class="rounded-xl bg-stone-50 px-3 py-4 text-sm text-stone-500">No external referrals recorded yet.</p>
                    @endforelse
                </section>
            </div>
        </section>

        <div class="mt-5 flex flex-col gap-1">
            <h2 class="text-xl font-bold tracking-tight text-stone-950">Site management</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('admin.categories.index') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Categories</span>
                <span class="text-sm text-stone-600">Organize articles into topics.</span>
            </a>
            <a href="{{ route('admin.tags.index') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Tags</span>
                <span class="text-sm text-stone-600">Label articles with flexible tags.</span>
            </a>
            <a href="{{ route('admin.media.index') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Media library</span>
                <span class="text-sm text-stone-600">Upload and reuse images across articles and pages.</span>
            </a>
            <a href="{{ route('admin.comments.index') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Comments</span>
                <span class="text-sm text-stone-600">Moderate submitted comments.</span>
            </a>
            <a href="{{ route('admin.menus.index') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Blog menus</span>
                <span class="text-sm text-stone-600">Manage links shown in the public header and footer.</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Users</span>
                <span class="text-sm text-stone-600">Search accounts and manage administrator access.</span>
            </a>
            <a href="{{ route('admin.settings.seo.edit') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Blog settings</span>
                <span class="text-sm text-stone-600">Manage SEO, footer description, and homepage hero text.</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="flex flex-col gap-2 rounded-2xl border border-stone-200 bg-white p-5 transition hover:border-stone-400">
                <span class="font-semibold text-stone-950">Profile</span>
                <span class="text-sm text-stone-600">Review your profile and account.</span>
            </a>
        </div>
    </section>
</x-admin-layout>

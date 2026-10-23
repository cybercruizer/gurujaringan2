<x-admin-layout>
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="flex flex-col gap-2">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Settings</p>
            <h1 class="text-3xl font-bold tracking-tight text-stone-950">Blog settings</h1>
            <p class="leading-7 text-stone-600">Manage the blog’s SEO defaults and public homepage and footer text.</p>
        </header>

        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('admin.settings.seo.update') }}" class="flex flex-col gap-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-8">
            @csrf
            @method('PUT')

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
                <label for="site_title" class="text-sm font-semibold text-stone-800">Blog title</label>
                <input id="site_title" name="site_title" type="text" value="{{ old('site_title', $settings->site_title) }}" maxlength="255" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                @error('site_title')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2">
                <label for="meta_description" class="text-sm font-semibold text-stone-800">Default meta description <span class="font-normal text-stone-500">(max 320 characters)</span></label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('meta_description', $settings->meta_description) }}</textarea>
                @error('meta_description')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2">
                <label for="default_og_image" class="text-sm font-semibold text-stone-800">Default social image URL <span class="font-normal text-stone-500">(optional)</span></label>
                <input id="default_og_image" name="default_og_image" type="url" value="{{ old('default_og_image', $settings->default_og_image) }}" maxlength="2048" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                @error('default_og_image')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

                <div class="flex flex-col gap-2 border-t border-stone-200 pt-5">
                    <h2 class="text-lg font-semibold text-stone-950">Footer</h2>
                    <label for="site_description" class="text-sm font-semibold text-stone-800">Site description</label>
                    <textarea id="site_description" name="site_description" rows="3" maxlength="1000" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('site_description', $settings->site_description ?: 'Thoughtful stories, ideas, and guides from our community.') }}</textarea>
                    @error('site_description')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-5 border-t border-stone-200 pt-5">
                    <h2 class="text-lg font-semibold text-stone-950">Homepage hero</h2>

                    <div class="flex flex-col gap-2">
                        <label for="hero_eyebrow" class="text-sm font-semibold text-stone-800">Eyebrow</label>
                        <input id="hero_eyebrow" name="hero_eyebrow" type="text" value="{{ old('hero_eyebrow', $settings->hero_eyebrow ?: 'Publishing new articles every week') }}" maxlength="150" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                        @error('hero_eyebrow')
                            <p class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="hero_title" class="text-sm font-semibold text-stone-800">Heading</label>
                        <input id="hero_title" name="hero_title" type="text" value="{{ old('hero_title', $settings->hero_title ?: 'Clear thinking about technology, from the people building it.') }}" maxlength="255" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                        @error('hero_title')
                            <p class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="hero_description" class="text-sm font-semibold text-stone-800">Description</label>
                        <textarea id="hero_description" name="hero_description" rows="3" maxlength="1000" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">{{ old('hero_description', $settings->hero_description ?: 'Practical articles, field notes, and lessons learned from our community of makers.') }}</textarea>
                        @error('hero_description')
                            <p class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="hero_cta_label" class="text-sm font-semibold text-stone-800">Button label</label>
                        <input id="hero_cta_label" name="hero_cta_label" type="text" value="{{ old('hero_cta_label', $settings->hero_cta_label ?: 'Read the latest articles') }}" maxlength="100" class="rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500">
                        @error('hero_cta_label')
                            <p class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-stone-200 pt-5">
                <label class="flex items-center gap-3 text-sm text-stone-700">
                    <input type="hidden" name="allow_indexing" value="0">
                    <input type="checkbox" name="allow_indexing" value="1" @checked(old('allow_indexing', $settings->allow_indexing)) class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                    Allow search engines to index the blog
                </label>
                @error('allow_indexing')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror

                <label class="flex items-center gap-3 text-sm text-stone-700">
                    <input type="hidden" name="sitemap_enabled" value="0">
                    <input type="checkbox" name="sitemap_enabled" value="1" @checked(old('sitemap_enabled', $settings->sitemap_enabled)) class="rounded border-stone-300 text-stone-900 focus:ring-stone-500">
                    Publish sitemap at <code>/sitemap.xml</code>
                </label>
                @error('sitemap_enabled')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end border-t border-stone-200 pt-5">
                <button type="submit" class="rounded-full bg-stone-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">Save blog settings</button>
            </div>
        </form>
    </section>
</x-admin-layout>

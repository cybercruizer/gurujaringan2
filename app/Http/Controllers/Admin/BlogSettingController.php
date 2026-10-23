<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBlogSettingRequest;
use App\Models\BlogSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BlogSettingController extends Controller
{
    public function edit(): View
    {
        $settings = BlogSetting::query()->first() ?? new BlogSetting([
            'site_title' => config('app.name'),
            'allow_indexing' => true,
            'sitemap_enabled' => true,
            'site_description' => 'Thoughtful stories, ideas, and guides from our community.',
            'hero_eyebrow' => 'Publishing new articles every week',
            'hero_title' => 'Clear thinking about technology, from the people building it.',
            'hero_description' => 'Practical articles, field notes, and lessons learned from our community of makers.',
            'hero_cta_label' => 'Read the latest articles',
        ]);

        return view('admin.settings.seo', compact('settings'));
    }

    public function update(UpdateBlogSettingRequest $request): RedirectResponse
    {
        $settings = BlogSetting::query()->first();

        if ($settings === null) {
            $settings = new BlogSetting;
        }

        $settings->fill($request->validated());
        $settings->save();

        return redirect()
            ->route('admin.settings.seo.edit')
            ->with('status', 'Blog settings updated.');
    }
}

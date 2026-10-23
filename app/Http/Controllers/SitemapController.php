<?php

namespace App\Http\Controllers;

use App\Models\BlogSetting;
use App\Models\Post;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function __invoke(): Sitemap
    {
        $settings = BlogSetting::query()->first();

        abort_if($settings?->sitemap_enabled === false || $settings?->allow_indexing === false, 404);

        $sitemap = Sitemap::create()->add(route('home'));

        Post::query()
            ->published()
            ->where('seo_noindex', false)
            ->orderBy('id')
            ->get(['id', 'slug', 'type', 'updated_at'])
            ->each(fn (Post $post) => $sitemap->add(
                Url::create($post->url())->setLastModificationDate($post->updated_at),
            ));

        return $sitemap;
    }
}

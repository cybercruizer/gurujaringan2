<?php

namespace App\Http\Controllers;

use App\Models\BlogSetting;
use App\Models\Post;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function __invoke(): SitemapIndex
    {
        $this->ensureSitemapIsEnabled();

        return SitemapIndex::create()
            ->add(route('sitemap.articles'))
            ->add(route('sitemap.pages'));
    }

    public function articles(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        return $this->createSitemap('article')->add(route('home'));
    }

    public function pages(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        return $this->createSitemap('page');
    }

    private function createSitemap(string $type): Sitemap
    {
        $sitemap = Sitemap::create();

        Post::query()
            ->published()
            ->where('type', $type)
            ->where('seo_noindex', false)
            ->orderBy('id')
            ->get(['id', 'slug', 'type', 'updated_at'])
            ->each(function (Post $post) use ($sitemap): void {
                $sitemap->add(
                    Url::create($post->url())->setLastModificationDate($post->updated_at),
                );
            });

        return $sitemap;
    }

    private function ensureSitemapIsEnabled(): void
    {
        $settings = BlogSetting::query()->first();

        abort_if($settings?->sitemap_enabled === false || $settings?->allow_indexing === false, 404);
    }
}

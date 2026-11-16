<?php

namespace App\Http\Controllers;

use App\Models\BlogSetting;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Uri;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function __invoke(): SitemapIndex
    {
        $this->ensureSitemapIsEnabled();

        return SitemapIndex::create()
            ->add($this->httpsUrl(route('sitemap.articles')))
            ->add($this->httpsUrl(route('sitemap.pages')))
            ->add($this->httpsUrl(route('sitemap.categories')))
            ->add($this->httpsUrl(route('sitemap.tags')))
            ->add($this->httpsUrl(route('sitemap.media')));
    }

    public function articles(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        return $this->createSitemap('article')->add($this->httpsUrl(route('home')));
    }

    public function pages(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        return $this->createSitemap('page');
    }

    public function categories(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        $sitemap = Sitemap::create();

        Category::query()
            ->whereHas('posts', function ($query): void {
                $query->where('type', 'article')
                    ->published()
                    ->where('seo_noindex', false);
            })
            ->orderBy('id')
            ->get(['slug'])
            ->each(function (Category $category) use ($sitemap): void {
                $sitemap->add($this->httpsUrl(route('categories.show', $category->slug)));
            });

        return $sitemap;
    }

    public function tags(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        $sitemap = Sitemap::create();

        Tag::query()
            ->whereHas('posts', function ($query): void {
                $query->where('type', 'article')
                    ->published()
                    ->where('seo_noindex', false);
            })
            ->orderBy('id')
            ->get(['slug'])
            ->each(function (Tag $tag) use ($sitemap): void {
                $sitemap->add($this->httpsUrl(route('tags.show', $tag->slug)));
            });

        return $sitemap;
    }

    public function media(): Sitemap
    {
        $this->ensureSitemapIsEnabled();

        $sitemap = Sitemap::create();

        Media::query()
            ->orderBy('id')
            ->get(['id', 'path'])
            ->each(function (Media $media) use ($sitemap): void {
                $sitemap->add($this->httpsUrl($media->url()));
            });

        return $sitemap;
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
                    Url::create($this->httpsUrl($post->url()))->setLastModificationDate($post->updated_at),
                );
            });

        return $sitemap;
    }

    private function httpsUrl(string $url): string
    {
        return Uri::of(url($url))->withScheme('https')->toString();
    }

    private function ensureSitemapIsEnabled(): void
    {
        $settings = BlogSetting::query()->first();

        abort_if($settings?->sitemap_enabled === false || $settings?->allow_indexing === false, 404);
    }
}

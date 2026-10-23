<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Fillable(['title', 'slug', 'type', 'content', 'excerpt', 'featured_image', 'featured_image_thumbnail', 'featured_media_id', 'published_at', 'seo_title', 'seo_description', 'seo_noindex', 'comments_enabled'])]
class Post extends Model
{
    use HasSlug, SoftDeletes;

    protected $attributes = [
        'type' => 'article',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function url(): string
    {
        return $this->type === 'page'
            ? route('pages.show', $this)
            : route('posts.show', $this);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'posts_categories', 'post_id', 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'seo_noindex' => 'boolean',
            'comments_enabled' => 'boolean',
        ];
    }

    protected function formattedPublishedAt(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->published_at?->format('Y-m-d'),
        );
    }
}

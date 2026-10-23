<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogSetting extends Model
{
    protected $fillable = [
        'site_title',
        'meta_description',
        'default_og_image',
        'site_description',
        'hero_eyebrow',
        'hero_title',
        'hero_description',
        'hero_cta_label',
        'allow_indexing',
        'sitemap_enabled',
    ];

    protected function casts(): array
    {
        return [
            'allow_indexing' => 'boolean',
            'sitemap_enabled' => 'boolean',
        ];
    }
}

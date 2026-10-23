<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

class RichTextSanitizer
{
    public function sanitize(string $html): string
    {
        $cacheDirectory = storage_path('framework/cache/htmlpurifier');
        File::ensureDirectoryExists($cacheDirectory);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', config('rich_text.allowed_html'));
        $config->set('Attr.AllowedClasses', array_fill_keys(config('rich_text.allowed_classes', []), true));
        $config->set('URI.AllowedSchemes', config('rich_text.allowed_uri_schemes'));
        $config->set('Cache.SerializerPath', $cacheDirectory);

        return (new HTMLPurifier($config))->purify($html);
    }
}

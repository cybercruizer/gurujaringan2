<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;
use RuntimeException;

class PostTableOfContents
{
    /**
     * @return array{content: string, headings: list<array{id: string, title: string, level: int}>}
     */
    public function generate(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorMode = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="post-content">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        if (! $loaded) {
            throw new RuntimeException('The post content could not be parsed to build its table of contents.');
        }

        $container = $document->getElementById('post-content');

        if (! $container instanceof DOMElement) {
            throw new RuntimeException('The post content container could not be found.');
        }

        $headings = [];
        $slugCounts = [];
        $headingNodes = iterator_to_array($document->getElementsByTagName('*'));

        foreach ($headingNodes as $heading) {
            if (! $heading instanceof DOMElement || ! in_array($heading->tagName, ['h2', 'h3', 'h4'], true)) {
                continue;
            }

            $title = trim($heading->textContent);

            if ($title === '') {
                continue;
            }

            $baseSlug = Str::slug($title) ?: 'section';
            $slugCounts[$baseSlug] = ($slugCounts[$baseSlug] ?? 0) + 1;
            $id = $slugCounts[$baseSlug] === 1
                ? 'section-'.$baseSlug
                : 'section-'.$baseSlug.'-'.$slugCounts[$baseSlug];
            $heading->setAttribute('id', $id);

            $headings[] = [
                'id' => $id,
                'title' => $title,
                'level' => (int) substr($heading->tagName, 1),
            ];
        }

        $content = '';

        foreach ($container->childNodes as $child) {
            $content .= $document->saveHTML($child);
        }

        return [
            'content' => $content,
            'headings' => $headings,
        ];
    }
}

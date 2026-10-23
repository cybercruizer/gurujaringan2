<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\View\View;

class TaxonomyPageController extends Controller
{
    public function category(Category $category): View
    {
        $posts = $category->posts()
            ->where('type', 'article')
            ->published()
            ->with('user')
            ->latest('published_at')
            ->paginate(9);

        return view('taxonomy.show', [
            'taxonomy' => $category,
            'taxonomyType' => 'Category',
            'posts' => $posts,
        ]);
    }

    public function tag(Tag $tag): View
    {
        $posts = $tag->posts()
            ->where('type', 'article')
            ->published()
            ->with('user')
            ->latest('published_at')
            ->paginate(9);

        return view('taxonomy.show', [
            'taxonomy' => $tag,
            'taxonomyType' => 'Tag',
            'posts' => $posts,
        ]);
    }
}

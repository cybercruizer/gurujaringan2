<?php

use App\Http\Controllers\Admin\BlogSettingController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PostManagementController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\PostContentImageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SiteSearchController;
use App\Http\Controllers\TaxonomyPageController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $posts = Post::published()
        ->where('type', 'article')
        ->with('user')
        ->latest('published_at')
        ->paginate(9);

    return view('welcome', compact('posts'));
})->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/sitemap-articles.xml', [SitemapController::class, 'articles'])->name('sitemap.articles');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/search', SiteSearchController::class)->name('search');
Route::get('/categories/{category:slug}', [TaxonomyPageController::class, 'category'])->name('categories.show');
Route::get('/tags/{tag:slug}', [TaxonomyPageController::class, 'tag'])->name('tags.show');

Route::middleware('auth')->group(function () {
    Route::get('/media/library', MediaLibraryController::class)->name('media.library');

    Route::post('/posts/content-images', PostContentImageController::class)
        ->middleware('throttle:content-image-uploads')
        ->name('posts.content-images.store');

    Route::resource('posts', PostController::class)->except(['index', 'show']);
});

Route::get('/page/{post:slug}', [PostController::class, 'show'])->name('pages.show');

Route::resource('posts', PostController::class)->only(['index', 'show']);

Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comment-submissions')
    ->name('comments.store');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings/seo', [BlogSettingController::class, 'edit'])->name('settings.seo.edit');
    Route::put('/settings/seo', [BlogSettingController::class, 'update'])->name('settings.seo.update');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('tags', TagController::class)->except(['show']);
    Route::get('/posts', [PostManagementController::class, 'index'])->name('posts.index');
    Route::patch('/posts/{post}/unpublish', [PostManagementController::class, 'unpublish'])->name('posts.unpublish');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])
        ->middleware('throttle:content-image-uploads')
        ->name('media.store');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::get('/menus', [MenuItemController::class, 'index'])->name('menus.index');
    Route::get('/menus/create', [MenuItemController::class, 'create'])->name('menus.create');
    Route::post('/menus', [MenuItemController::class, 'store'])->name('menus.store');
    Route::get('/menus/{menu}/edit', [MenuItemController::class, 'edit'])->name('menus.edit');
    Route::put('/menus/{menu}', [MenuItemController::class, 'update'])->name('menus.update');
    Route::delete('/menus/{menu}', [MenuItemController::class, 'destroy'])->name('menus.destroy');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::get('/admin', DashboardController::class)
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportArticleBackupRequest;
use App\Services\ArticleBackupManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleBackupController extends Controller
{
    public function index(): View
    {
        return view('admin.article-backups.index');
    }

    public function export(ArticleBackupManager $backups): BinaryFileResponse
    {
        $path = $backups->export();

        return response()
            ->download($path, 'guru-jaringan-articles-'.now()->format('Y-m-d-His').'.zip')
            ->deleteFileAfterSend();
    }

    public function import(ImportArticleBackupRequest $request, ArticleBackupManager $backups): RedirectResponse
    {
        $result = $backups->import($request->file('backup'), $request->user());

        return redirect()
            ->route('admin.article-backups.index')
            ->with('status', "{$result['created']} artikel ditambahkan dan {$result['updated']} artikel diperbarui.");
    }
}

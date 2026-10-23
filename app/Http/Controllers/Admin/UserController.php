<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $validated['search'] ?? null;

        $users = User::query()
            ->when($search !== null, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->withCount(['posts', 'comments'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,member'],
        ]);
        $grantAdmin = $validated['role'] === 'admin';

        if (! $grantAdmin && $request->user()->is($user)) {
            return back()->with('status', 'You cannot remove your own administrator access.');
        }

        $roleUpdated = DB::transaction(function () use ($user, $grantAdmin): bool {
            $adminCount = User::query()
                ->where('is_admin', true)
                ->lockForUpdate()
                ->count();

            if ($user->is_admin && ! $grantAdmin && $adminCount <= 1) {
                return false;
            }

            $user->forceFill(['is_admin' => $grantAdmin])->save();

            return true;
        });

        return back()->with(
            'status',
            $roleUpdated ? 'User role updated.' : 'The last administrator cannot be demoted.',
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('status', 'You cannot delete your own account from user management.');
        }

        $deletionResult = DB::transaction(function () use ($user): string {
            $user = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->posts()->exists() || $user->comments()->exists()) {
                return 'has_content';
            }

            $adminCount = User::query()
                ->where('is_admin', true)
                ->lockForUpdate()
                ->count();

            if ($user->is_admin && $adminCount <= 1) {
                return 'last_admin';
            }

            $avatar = $user->avatar;

            if ($avatar !== null && ! Storage::disk('public')->delete($avatar)) {
                throw new \RuntimeException('The user avatar could not be deleted.');
            }

            $user->delete();

            return 'deleted';
        });

        $message = match ($deletionResult) {
            'has_content' => 'This user has authored posts or comments and cannot be deleted.',
            'last_admin' => 'The last administrator cannot be deleted.',
            default => 'User deleted.',
        };

        return back()->with('status', $message);
    }
}

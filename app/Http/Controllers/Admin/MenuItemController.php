<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MenuItemController extends Controller
{
    public function index(): View
    {
        $menuItems = MenuItem::query()
            ->orderBy('location')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.menus.index', compact('menuItems'));
    }

    public function create(): View
    {
        return view('admin.menus.form', [
            'menuItem' => new MenuItem([
                'location' => 'header',
                'position' => 0,
                'is_visible' => true,
                'is_external' => false,
            ]),
            'action' => route('admin.menus.store'),
            'method' => 'POST',
        ]);
    }

    public function store(SaveMenuItemRequest $request): RedirectResponse
    {
        MenuItem::query()->create($request->validated());

        return redirect()
            ->route('admin.menus.index')
            ->with('status', 'Menu link created.');
    }

    public function edit(MenuItem $menu): View
    {
        return view('admin.menus.form', [
            'menuItem' => $menu,
            'action' => route('admin.menus.update', $menu),
            'method' => 'PUT',
        ]);
    }

    public function update(SaveMenuItemRequest $request, MenuItem $menu): RedirectResponse
    {
        $menu->update($request->validated());

        return redirect()
            ->route('admin.menus.index')
            ->with('status', 'Menu link updated.');
    }

    public function destroy(MenuItem $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('status', 'Menu link deleted.');
    }
}

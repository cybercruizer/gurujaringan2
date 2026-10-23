<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_menu_link_and_public_header_displays_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.menus.store'), [
                'label' => 'About us',
                'url' => '/about',
                'location' => 'header',
                'is_external' => '0',
                'is_visible' => '1',
                'position' => '1',
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menu_items', [
            'label' => 'About us',
            'url' => '/about',
            'location' => 'header',
            'is_visible' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('About us')
            ->assertSee('href="/about"', false);
    }

    public function test_admin_can_edit_and_delete_a_menu_link(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $menuItem = MenuItem::query()->create([
            'label' => 'Old label',
            'url' => '/old',
            'location' => 'footer',
            'is_external' => false,
            'is_visible' => true,
            'position' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.menus.update', $menuItem), [
                'label' => 'New label',
                'url' => 'https://example.com',
                'location' => 'footer',
                'is_external' => '1',
                'is_visible' => '1',
                'position' => '2',
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'label' => 'New label',
            'url' => 'https://example.com',
            'is_external' => true,
            'position' => 2,
        ]);

        $this->delete(route('admin.menus.destroy', $menuItem))
            ->assertRedirect(route('admin.menus.index'));

        $this->assertModelMissing($menuItem);
    }

    public function test_menu_validation_rejects_external_urls_for_internal_links(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.menus.create'))
            ->post(route('admin.menus.store'), [
                'label' => 'Unsafe',
                'url' => '//example.com',
                'location' => 'header',
                'is_external' => '0',
                'is_visible' => '1',
                'position' => '0',
            ])
            ->assertRedirect(route('admin.menus.create'))
            ->assertSessionHasErrors([
                'url' => 'Internal links must be a local path beginning with one slash.',
            ]);

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_menu_validation_rejects_non_http_external_urls(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.menus.create'))
            ->post(route('admin.menus.store'), [
                'label' => 'Unsafe external link',
                'url' => 'javascript:alert(1)',
                'location' => 'header',
                'is_external' => '1',
                'is_visible' => '1',
                'position' => '0',
            ])
            ->assertRedirect(route('admin.menus.create'))
            ->assertSessionHasErrors([
                'url' => 'External links must use a valid HTTP or HTTPS URL.',
            ]);

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_non_admin_cannot_manage_menu_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.menus.index'))
            ->assertForbidden();

        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_hidden_menu_links_are_not_rendered_publicly(): void
    {
        MenuItem::query()->create([
            'label' => 'Hidden link marker',
            'url' => '/hidden',
            'location' => 'footer',
            'is_external' => false,
            'is_visible' => false,
            'position' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Hidden link marker');
    }

    public function test_external_footer_links_open_safely_in_a_new_tab(): void
    {
        MenuItem::query()->create([
            'label' => 'External resource',
            'url' => 'https://example.test/resources',
            'location' => 'footer',
            'is_external' => true,
            'is_visible' => true,
            'position' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('External resource')
            ->assertSee('href="https://example.test/resources"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false);
    }
}

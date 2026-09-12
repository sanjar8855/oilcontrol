<?php

namespace Tests\Feature;

use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class GlobalCategoryManagementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_a_superadmin_can_view_the_global_categories_index(): void
    {
        $admin = $this->createSuperAdmin();
        GlobalCategory::create(['name' => 'Yog\'', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('global-categories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Yog\'')
        );
    }

    public function test_a_superadmin_can_create_a_global_category(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->post(route('global-categories.store'), [
            'name' => 'Filtrlar',
        ]);

        $response->assertRedirect(route('global-categories.index'));
        $this->assertDatabaseHas('global_categories', ['name' => 'Filtrlar', 'is_active' => true]);
    }

    public function test_creating_a_global_category_requires_a_unique_name(): void
    {
        $admin = $this->createSuperAdmin();
        GlobalCategory::create(['name' => 'Filtrlar', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('global-categories.store'), [
            'name' => 'Filtrlar',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, GlobalCategory::count());
    }

    public function test_a_superadmin_can_rename_and_toggle_a_global_category(): void
    {
        $admin = $this->createSuperAdmin();
        $category = GlobalCategory::create(['name' => 'Yog\'', 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('global-categories.update', $category), [
            'name' => 'Motor moylari',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('global-categories.index'));
        $this->assertDatabaseHas('global_categories', [
            'id' => $category->id,
            'name' => 'Motor moylari',
            'is_active' => false,
        ]);
    }

    public function test_a_superadmin_can_delete_an_unused_global_category(): void
    {
        $admin = $this->createSuperAdmin();
        $category = GlobalCategory::create(['name' => 'Yog\'', 'is_active' => true]);

        $response = $this->actingAs($admin)->delete(route('global-categories.destroy', $category));

        $response->assertRedirect(route('global-categories.index'));
        $this->assertDatabaseMissing('global_categories', ['id' => $category->id]);
    }

    public function test_deleting_a_global_category_with_products_is_blocked(): void
    {
        $admin = $this->createSuperAdmin();
        $category = GlobalCategory::create(['name' => 'Yog\'', 'is_active' => true]);
        GlobalProduct::create(['name' => 'Motor moyi', 'unit' => 'litr', 'is_active' => true, 'global_category_id' => $category->id]);

        $response = $this->actingAs($admin)->delete(route('global-categories.destroy', $category));

        $response->assertRedirect(route('global-categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('global_categories', ['id' => $category->id]);
    }

    public function test_a_director_cannot_manage_global_categories(): void
    {
        [$director] = $this->createDirectorWithWorkshop();

        $response = $this->actingAs($director)->get(route('global-categories.index'));

        $response->assertForbidden();
    }
}

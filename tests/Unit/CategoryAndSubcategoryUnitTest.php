<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryAndSubcategoryUnitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_category_with_fillable_attributes(): void
    {
        $category = Category::create([
            'name' => 'Categoría Test '.uniqid(),
            'description' => 'Descripción detallada de la categoría de prueba',
        ]);

        $this->assertNotNull($category->id);
        $this->assertNotEmpty($category->name);
        $this->assertEquals('Descripción detallada de la categoría de prueba', $category->description);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
        ]);
    }

    public function test_can_create_category_without_description(): void
    {
        $category = Category::create([
            'name' => 'Categoría Sin Descripción '.uniqid(),
        ]);

        $this->assertNotNull($category->id);
        $this->assertNull($category->description);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'description' => null,
        ]);
    }

    public function test_can_create_subcategory_associated_with_category(): void
    {
        $category = Category::create([
            'name' => 'Categoría Padre '.uniqid(),
            'description' => 'Categoría para asociar subcategoría',
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Subcategoría Test '.uniqid(),
        ]);

        $this->assertNotNull($subcategory->id);
        $this->assertEquals($category->id, $subcategory->category_id);
        $this->assertDatabaseHas('subcategories', [
            'id' => $subcategory->id,
            'category_id' => $category->id,
            'name' => $subcategory->name,
        ]);
    }

    public function test_category_has_many_subcategories_relationship(): void
    {
        $category = Category::create([
            'name' => 'Categoría con Hijos '.uniqid(),
        ]);

        $sub1 = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Subcategoría Uno '.uniqid(),
        ]);

        $sub2 = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Subcategoría Dos '.uniqid(),
        ]);

        $this->assertInstanceOf(HasMany::class, $category->subcategories());
        $this->assertCount(2, $category->subcategories);
        $this->assertTrue($category->subcategories->contains($sub1));
        $this->assertTrue($category->subcategories->contains($sub2));
    }

    public function test_subcategory_belongs_to_category_relationship(): void
    {
        $category = Category::create([
            'name' => 'Categoría Padre BelongsTo '.uniqid(),
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Subcategoría BelongsTo '.uniqid(),
        ]);

        $this->assertInstanceOf(BelongsTo::class, $subcategory->category());
        $this->assertNotNull($subcategory->category);
        $this->assertEquals($category->id, $subcategory->category->id);
        $this->assertEquals($category->name, $subcategory->category->name);
    }

    public function test_category_has_many_through_items_relationship(): void
    {
        $category = new Category;
        $this->assertInstanceOf(HasManyThrough::class, $category->items());
    }
}

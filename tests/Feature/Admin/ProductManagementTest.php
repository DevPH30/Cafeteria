<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_product_with_a_photo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(), 'web');

        $response = $this->post(route('admin.products.store'), [
            'name' => 'Espresso especial',
            'description' => 'Espresso intenso preparado na hora.',
            'price' => '12.50',
            'category' => 'coffee',
            'image' => UploadedFile::fake()->image('espresso.png'),
        ]);

        $response->assertRedirect();
        $product = Product::query()->firstOrFail();
        $this->assertSame('Espresso especial', $product->name);
        $this->assertSame('coffee', $product->category);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_product_fields_and_category_are_validated(): void
    {
        $this->actingAs(User::factory()->create(), 'web');

        $this->from('/admin')
            ->post(route('admin.products.store'), [
                'name' => '',
                'price' => '0',
                'category' => 'invalid',
            ])
            ->assertRedirect('/admin')
            ->assertSessionHasErrors([
                'name',
                'description',
                'price',
                'category',
                'image',
            ]);
    }

    public function test_administrator_can_edit_and_remove_a_product(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(), 'web');
        $product = Product::create([
            'name' => 'Produto antigo',
            'description' => 'Descrição do produto antigo.',
            'price' => 10,
            'category' => 'coffee',
            'image' => UploadedFile::fake()->image('old.png')->store('products', 'public'),
        ]);

        $this->put(route('admin.products.update', $product), [
            'name' => 'Produto atualizado',
            'description' => 'Descrição do produto atualizado.',
            'price' => '15.90',
            'category' => 'cupcakes',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto atualizado',
            'category' => 'cupcakes',
            'price' => 15.90,
        ]);

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}

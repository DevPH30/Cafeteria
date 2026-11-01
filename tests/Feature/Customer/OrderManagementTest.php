<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_a_delivery_order_with_registered_address(): void
    {
        Storage::fake('public');
        $customer = Customer::create([
            'name' => 'Cliente Aroma',
            'email' => 'cliente@example.com',
            'password' => 'password',
            'phone' => '31999999999',
            'zip_code' => '35000-000',
            'neighborhood' => 'Centro',
            'city' => 'Governador Valadares',
            'state' => 'MG',
            'address' => 'Rua do Café, 10',
        ]);
        $product = Product::create([
            'name' => 'Café especial',
            'description' => 'Café preparado na hora.',
            'category' => 'coffee',
            'price' => 12.50,
            'image' => 'products/cafe.png',
        ]);

        $response = $this->actingAs($customer, 'customer')->post('/cliente/pedidos', [
            'items' => [['id' => $product->id, 'quantity' => 2]],
            'fulfillment' => 'delivery',
            'payment_method' => 'pix',
            'delivery_fee' => 10,
            'address' => 'Rua do Café, 10, Centro, Governador Valadares - MG, CEP 35000-000',
        ]);

        $response->assertRedirect('/acompanhar-pedido');
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'fulfillment' => 'delivery',
            'delivery_fee' => 10,
            'total' => 35,
        ]);
    }

    public function test_customer_can_change_the_delivery_address(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Aroma',
            'email' => 'outro@example.com',
            'password' => 'password',
            'phone' => '31999999999',
            'zip_code' => '35000-000',
            'neighborhood' => 'Centro',
            'city' => 'Governador Valadares',
            'state' => 'MG',
            'address' => 'Rua do Café, 10',
        ]);
        $product = Product::create([
            'name' => 'Café filtrado',
            'description' => 'Café coado na hora.',
            'category' => 'coffee',
            'price' => 10,
            'image' => 'products/cafe-filtrado.png',
        ]);

        $this->actingAs($customer, 'customer')
            ->post('/cliente/pedidos', [
                'items' => [['id' => $product->id, 'quantity' => 1]],
                'fulfillment' => 'delivery',
                'payment_method' => 'pickup',
                'delivery_fee' => 10,
                'address' => 'Rua diferente, Centro, Governador Valadares - MG, CEP 35000-000',
            ])
            ->assertRedirect('/acompanhar-pedido');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'delivery_address' => 'Rua diferente, Centro, Governador Valadares - MG, CEP 35000-000',
        ]);
    }

    public function test_customer_can_update_profile_data(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Aroma',
            'email' => 'perfil@example.com',
            'password' => 'password',
            'phone' => '31999999999',
            'zip_code' => '35000-000',
            'neighborhood' => 'Centro',
            'city' => 'Governador Valadares',
            'state' => 'MG',
            'address' => 'Rua do Café, 10',
        ]);

        $this->actingAs($customer, 'customer')
            ->put('/cliente/perfil', [
                'name' => 'Cliente Atualizado',
                'email' => 'atualizado@example.com',
                'phone' => '31988888888',
                'zip_code' => '35000-111',
                'neighborhood' => 'Bairro Novo',
                'city' => 'Ipatinga',
                'state' => 'MG',
                'address' => 'Avenida Aroma, 20',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Cliente Atualizado',
            'email' => 'atualizado@example.com',
            'address' => 'Avenida Aroma, 20',
        ]);
    }
}

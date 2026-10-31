<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_creates_a_separate_customer_account(): void
    {
        $this->get(route('customer.register'))->assertOk();
        User::factory()->create(['email' => 'client@example.com']);

        $response = $this->post(route('customer.register.store'), $this->validRegistration());

        $customer = Customer::query()->firstOrFail();

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('web');
        $this->assertSame('client@example.com', $customer->email);
        $this->assertTrue(Hash::check('secure-password-123', $customer->password));
        $this->assertDatabaseHas('customers', [
            'email' => 'client@example.com',
            'phone' => '(11) 99999-9999',
            'zip_code' => '01001-000',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
            'address' => 'Praça da Sé, 10',
        ]);
    }

    public function test_customer_registration_validates_address_and_password_fields(): void
    {
        $data = $this->validRegistration();
        $data['phone'] = 'abc';
        $data['zip_code'] = '123';
        $data['state'] = 'São Paulo';
        $data['password_confirmation'] = 'different-password';

        $this->from('/cadastro')
            ->post(route('customer.register.store'), $data)
            ->assertRedirect('/cadastro')
            ->assertSessionHasErrors(['phone', 'zip_code', 'state', 'password']);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_customer_login_and_logout_do_not_change_the_admin_guard(): void
    {
        $admin = User::factory()->create();
        $customer = Customer::create($this->validRegistration());
        $this->actingAs($admin, 'web');

        $this->post(route('customer.login'), [
            'email' => $customer->email,
            'password' => 'secure-password-123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertAuthenticatedAs($customer, 'customer');

        $this->post(route('customer.logout'))->assertRedirect('/');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertGuest('customer');
    }

    public function test_invalid_customer_login_is_rejected(): void
    {
        $this->from('/')
            ->post(route('customer.login'), [
                'email' => 'unknown@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_customer_sessions_cannot_access_the_admin_area(): void
    {
        $customer = Customer::create($this->validRegistration());
        $this->actingAs($customer, 'customer');

        $this->get(route('cafe-admin'))->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

    /**
     * @return array<string, string>
     */
    private function validRegistration(): array
    {
        return [
            'name' => 'Cliente Aroma',
            'email' => 'client@example.com',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
            'phone' => '(11) 99999-9999',
            'zip_code' => '01001-000',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'sp',
            'address' => 'Praça da Sé, 10',
        ];
    }
}

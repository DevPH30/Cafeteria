<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_administrator_registration(): void
    {
        $this->get(route('admin.administrators.create'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.administrators.store'), $this->validAdministrator())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_customer_session_cannot_register_an_administrator(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente',
            'email' => 'customer@example.com',
            'password' => 'customer-password',
            'phone' => '(61) 99999-9999',
            'zip_code' => '70000-000',
            'neighborhood' => 'Centro',
            'city' => 'Brasília',
            'state' => 'DF',
            'address' => 'Rua 1, 10',
        ]);
        $this->actingAs($customer, 'customer');

        $this->get(route('admin.administrators.create'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_administrator_can_create_another_administrator(): void
    {
        $administrator = User::factory()->create();
        $this->actingAs($administrator, 'web');

        $this->get(route('admin.administrators.create'))->assertOk();

        $this->post(route('admin.administrators.store'), $this->validAdministrator())
            ->assertRedirect('/admin');

        $created = User::query()->where('email', 'new-admin@example.com')->firstOrFail();

        $this->assertSame('Ana Admin', $created->name);
        $this->assertSame('(61) 99999-9999', $created->phone);
        $this->assertNotNull($created->email_verified_at);
        $this->assertTrue(Hash::check('safe-admin-password-123', $created->password));
    }

    public function test_administrator_can_log_out_from_the_admin_dashboard(): void
    {
        $administrator = User::factory()->create();
        $this->actingAs($administrator, 'web');

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest('web');
    }

    public function test_administrator_registration_validates_required_fields_and_duplicates(): void
    {
        $administrator = User::factory()->create();
        $this->actingAs($administrator, 'web');

        $this->from(route('admin.administrators.create'))
            ->post(route('admin.administrators.store'), [
                'name' => '',
                'email' => 'invalid',
                'phone' => 'abc',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ])
            ->assertRedirect(route('admin.administrators.create'))
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);

        $this->from(route('admin.administrators.create'))
            ->post(route('admin.administrators.store'), [
                ...$this->validAdministrator(),
                'email' => $administrator->email,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_open_fortify_administrator_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    /**
     * @return array<string, string>
     */
    private function validAdministrator(): array
    {
        return [
            'name' => 'Ana Admin',
            'email' => 'new-admin@example.com',
            'phone' => '(61) 99999-9999',
            'password' => 'safe-admin-password-123',
            'password_confirmation' => 'safe-admin-password-123',
        ];
    }
}

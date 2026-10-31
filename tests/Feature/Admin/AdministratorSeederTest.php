<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdministratorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_the_first_administrator_from_environment_password(): void
    {
        config(['admin.initial_password' => 'initial-admin-password']);

        $this->seed(AdministratorSeeder::class);

        $administrator = User::query()->where('email', 'adm@adm.com')->firstOrFail();

        $this->assertSame('administrador', $administrator->name);
        $this->assertSame('61 999999999', $administrator->phone);
        $this->assertNotNull($administrator->email_verified_at);
        $this->assertTrue(Hash::check('initial-admin-password', $administrator->password));
    }

    public function test_seed_does_not_reset_an_existing_administrator_password(): void
    {
        config(['admin.initial_password' => 'initial-admin-password']);
        $administrator = User::factory()->create([
            'name' => 'Old Admin Name',
            'email' => 'adm@adm.com',
            'password' => 'custom-existing-password',
        ]);

        $this->seed(AdministratorSeeder::class);

        $administrator->refresh();

        $this->assertSame('administrador', $administrator->name);
        $this->assertSame('61 999999999', $administrator->phone);
        $this->assertTrue(Hash::check('custom-existing-password', $administrator->password));
        $this->assertFalse(Hash::check('initial-admin-password', $administrator->password));
    }

    public function test_seeded_administrator_can_log_in_and_access_the_admin_area(): void
    {
        config(['admin.initial_password' => 'initial-admin-password']);
        $this->seed(AdministratorSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'adm@adm.com',
            'password' => 'initial-admin-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs(
            User::query()->where('email', 'adm@adm.com')->firstOrFail(),
            'web',
        );

        $this->get('/admin')->assertOk();
    }
}

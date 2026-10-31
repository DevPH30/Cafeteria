<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('admin.initial_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Configure ADMIN_INITIAL_PASSWORD no arquivo .env antes de executar o seed.');
        }

        $administrator = User::firstOrNew([
            'email' => 'adm@adm.com',
        ]);

        $administrator->fill([
            'name' => 'administrador',
            'phone' => '61 999999999',
            'email_verified_at' => now(),
        ]);

        if (! $administrator->exists) {
            $administrator->password = $password;
        }

        $administrator->save();
    }
}

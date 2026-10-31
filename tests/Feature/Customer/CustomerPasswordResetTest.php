<?php

namespace Tests\Feature\Customer;

use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_and_use_a_password_reset_link(): void
    {
        Notification::fake();
        $customer = Customer::create([
            'name' => 'Cliente Aroma',
            'email' => 'client@example.com',
            'password' => 'old-password',
            'phone' => '(11) 99999-9999',
            'zip_code' => '01001-000',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
            'address' => 'Praça da Sé, 10',
        ]);

        $this->get(route('customer.password.request'))->assertOk();

        $this->post(route('customer.password.email'), [
            'email' => $customer->email,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $customer,
            CustomerResetPasswordNotification::class,
            function (CustomerResetPasswordNotification $notification) use ($customer): bool {
                $mail = $notification->toMail($customer);

                $this->assertStringContainsString(
                    '/cliente/redefinir-senha/'.$notification->token,
                    $mail->actionUrl,
                );

                $this->get(route('customer.password.reset', [
                    'token' => $notification->token,
                    'email' => $customer->email,
                ]))->assertOk();

                $this->post(route('customer.password.update'), [
                    'token' => $notification->token,
                    'email' => $customer->email,
                    'password' => 'new-secure-password-123',
                    'password_confirmation' => 'new-secure-password-123',
                ])->assertRedirect('/');

                return true;
            },
        );

        $this->assertTrue(Hash::check(
            'new-secure-password-123',
            $customer->fresh()->password,
        ));
    }

    public function test_customer_password_reset_does_not_send_links_to_admin_accounts(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'admin@example.com']);

        $this->from('/cliente/esqueci-senha')
            ->post(route('customer.password.email'), [
                'email' => 'admin@example.com',
            ])
            ->assertRedirect('/cliente/esqueci-senha')
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('customer_password_reset_tokens', 0);
    }

    public function test_customer_password_reset_rejects_invalid_tokens(): void
    {
        $this->from('/cliente/redefinir-senha/invalid')
            ->post(route('customer.password.update'), [
                'token' => 'invalid-token',
                'email' => 'client@example.com',
                'password' => 'new-secure-password-123',
                'password_confirmation' => 'new-secure-password-123',
            ])
            ->assertRedirect('/cliente/redefinir-senha/invalid')
            ->assertSessionHasErrors('email');
    }
}

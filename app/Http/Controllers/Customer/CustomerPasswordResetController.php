<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPasswordResetController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('customer/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function email(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $status = Password::broker('customers')->sendResetLink([
            'email' => Str::lower(trim($validated['email'])),
        ]);

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Aguarde antes de solicitar outro link de redefinição.',
            ]);
        }

        return back()->with(
            'status',
            'Se houver uma conta associada a este e-mail, enviaremos um link para redefinir a senha.',
        );
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('customer/ResetPassword', [
            'email' => $request->query('email', ''),
            'token' => $token,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'password' => ['required', 'string', PasswordRule::default() ?? PasswordRule::min(8), 'confirmed'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe uma nova senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password): void {
                $customer->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'Não foi possível redefinir a senha. Solicite um novo link.',
            ]);
        }

        return redirect('/')->with('customerStatus', 'Senha redefinida. Entre com sua nova senha.');
    }
}

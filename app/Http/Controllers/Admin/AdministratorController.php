<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdministratorController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/AdminRegister', [
            'passwordRules' => (Password::default() ?? Password::min(8))->toPasswordRulesString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:24', 'regex:/^\+?[0-9\s().-]+$/'],
            'password' => ['required', 'string', Password::default() ?? Password::min(8), 'confirmed'],
        ], [
            'name.required' => 'Informe o nome do administrador.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'phone.required' => 'Informe o telefone.',
            'phone.regex' => 'Informe um telefone válido.',
            'password.required' => 'Informe uma senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $phoneDigits = preg_replace('/\D+/', '', $validated['phone']);

        if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 13) {
            return back()->withErrors([
                'phone' => 'Informe um telefone com DDD válido.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        return redirect('/admin')->with('adminStatus', 'Administrador cadastrado com sucesso.');
    }
}

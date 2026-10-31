<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAuthController extends Controller
{
    public function register(): Response
    {
        return Inertia::render('customer/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'state' => Str::upper(trim((string) $request->input('state'))),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:customers,email'],
            'password' => ['required', 'string', Password::default() ?? Password::min(8), 'confirmed'],
            'phone' => ['required', 'string', 'max:24', 'regex:/^\+?[0-9\s().-]+$/'],
            'zip_code' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2', 'alpha'],
            'address' => ['required', 'string', 'max:191'],
        ], [
            'name.required' => 'Informe seu nome.',
            'name.max' => 'O nome deve ter no máximo 191 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe uma senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'phone.required' => 'Informe seu telefone.',
            'phone.regex' => 'Informe um telefone válido, incluindo o DDD.',
            'zip_code.required' => 'Informe seu CEP.',
            'zip_code.regex' => 'Informe um CEP válido no formato 00000-000.',
            'neighborhood.required' => 'Informe seu bairro.',
            'city.required' => 'Informe sua cidade.',
            'state.required' => 'Informe seu estado.',
            'state.size' => 'Informe a sigla do estado com duas letras.',
            'state.alpha' => 'O estado deve conter apenas letras.',
            'address.required' => 'Informe seu endereço.',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $phoneDigits = preg_replace('/\D+/', '', (string) $request->input('phone'));

            if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 13) {
                $validator->errors()->add('phone', 'Informe um telefone com DDD válido.');
            }
        });

        $validated = $validator->validate();
        $customer = Customer::create($validated);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('customerStatus', 'Cadastro realizado com sucesso.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $remember = (bool) ($credentials['remember'] ?? false);
        unset($credentials['remember']);

        $customer = Customer::query()->where('email', $credentials['email'])->first();

        if (! $customer || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        Auth::guard('customer')->login($customer, $remember);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('customerStatus', 'Login realizado com sucesso.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

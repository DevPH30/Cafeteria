<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'state' => Str::upper(trim((string) $request->input('state'))),
        ]);

        $customer = Auth::guard('customer')->user();
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:customers,email,'.$customer->id],
            'phone' => ['required', 'string', 'max:24', 'regex:/^\+?[0-9\s().-]+$/'],
            'zip_code' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2', 'alpha'],
            'address' => ['required', 'string', 'max:191'],
        ], [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'phone.regex' => 'Informe um telefone válido, incluindo o DDD.',
            'zip_code.regex' => 'Informe um CEP válido no formato 00000-000.',
            'state.size' => 'Informe a sigla do estado com duas letras.',
            'state.alpha' => 'O estado deve conter apenas letras.',
        ])->validate();

        $phoneDigits = preg_replace('/\D+/', '', (string) $validated['phone']);
        if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 13) {
            return back()->withErrors(['phone' => 'Informe um telefone com DDD válido.']);
        }

        $customer->update($validated);

        return back()->with('customerStatus', 'Dados cadastrais atualizados com sucesso.');
    }
}

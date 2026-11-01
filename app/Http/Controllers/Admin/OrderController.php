<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function updateStatus(Request $request, string $order): RedirectResponse
    {
        $order = Order::query()->where('number', $order)->firstOrFail();
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in($order->fulfillment === 'pickup'
                    ? ['Em Produção', 'Está pronto o pedido', 'Pedido retirado', 'Cancelado']
                    : ['Em Produção', 'Pronto para Entrega', 'Saiu para Entrega', 'Entregue', 'Cancelado']),
            ],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('adminStatus', 'Status do pedido atualizado.');
    }
}

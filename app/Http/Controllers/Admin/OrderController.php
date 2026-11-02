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
                Rule::in($order->scheduled_date !== null
                    ? ['aguardando retorno', 'pedido confirmado', 'pedido retirado', 'pedido cancelado']
                    : ($order->fulfillment === 'pickup'
                    ? ['Em Produção', 'Está pronto o pedido', 'Pedido retirado', 'Cancelado']
                    : ['Em Produção', 'Pronto para Entrega', 'Saiu para Entrega', 'Entregue', 'Cancelado'])),
            ],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('adminStatus', 'Status do pedido atualizado.');
    }

    public function updateReservation(Request $request, string $order): RedirectResponse
    {
        $reservation = Order::query()
            ->where('number', $order)
            ->whereNotNull('scheduled_date')
            ->firstOrFail();

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['aguardando retorno', 'pedido confirmado', 'pedido retirado', 'pedido cancelado']),
            ],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'value' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'detail' => ['nullable', 'string', 'max:2000'],
        ]);

        $reservation->update([
            'status' => $validated['status'],
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'],
            'value' => $validated['value'] ?? null,
            'detail' => $validated['detail'] ?? null,
            'subtotal' => $validated['value'] ?? 0,
            'total' => $validated['value'] ?? 0,
        ]);

        return back()->with('adminStatus', 'Encomenda atualizada.');
    }
}

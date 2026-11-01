<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'fulfillment' => ['required', Rule::in(['pickup', 'delivery'])],
            'payment_method' => [
                'required',
                Rule::in(['pickup', 'pix', 'credit', 'debit']),
            ],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
            'address' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:700'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'items.required' => 'Adicione ao menos um produto ao pedido.',
            'fulfillment.required' => 'Selecione a forma de recebimento.',
            'address.required_if' => 'Informe o endereço de entrega.',
        ]);

        $customer = Auth::guard('customer')->user();
        $address = $validated['address'] ?? null;

        if ($validated['fulfillment'] !== 'delivery') {
            $validated['delivery_fee'] = 0;
            $address = null;
        }

        $products = Product::query()
            ->whereIn('id', collect($validated['items'])->pluck('id'))
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($validated['items'])) {
            return back()->withErrors(['items' => 'Um dos produtos do pedido não está disponível.']);
        }

        $items = [];
        $subtotal = 0.0;
        foreach ($validated['items'] as $item) {
            $product = $products->get($item['id']);
            $quantity = (int) $item['quantity'];
            $price = (float) $product->price;
            $subtotal += $price * $quantity;
            $items[] = [
                'id' => (string) $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        $deliveryFee = $validated['fulfillment'] === 'delivery'
            ? (float) $validated['delivery_fee']
            : 0.0;
        $total = $subtotal + $deliveryFee;
        $order = DB::transaction(function () use ($customer, $validated, $address, $items, $subtotal, $deliveryFee, $total): Order {
            return Order::create([
                'customer_id' => $customer->id,
                'number' => $this->uniqueNumber(),
                'status' => 'Em Produção',
                'fulfillment' => $validated['fulfillment'],
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'items' => $items,
                'delivery_address' => $address,
                'scheduled_date' => $validated['scheduled_date'] ?? null,
                'scheduled_time' => $validated['scheduled_time'] ?? null,
                'message' => $validated['message'] ?? null,
            ]);
        });

        return redirect('/acompanhar-pedido')->with('customerOrderNumber', $order->number);
    }

    private function uniqueNumber(): string
    {
        do {
            $number = (string) random_int(10000000, 99999999);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}

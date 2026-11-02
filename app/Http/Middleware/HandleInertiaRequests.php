<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'customer' => Auth::guard('customer')->user()?->only([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'neighborhood',
                    'city',
                    'state',
                    'zip_code',
                ]),
            ],
            'customerStatus' => fn () => $request->session()->get('customerStatus'),
            'adminStatus' => fn () => $request->session()->get('adminStatus'),
            'catalogProducts' => fn () => Product::query()->latest()->get()->map(fn (Product $product) => [
                'id' => (string) $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'group' => $product->group,
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'minimumStock' => $product->minimum_stock,
                'description' => $product->description,
                'image' => str_starts_with($product->image, 'http') ? $product->image : asset('storage/'.$product->image),
            ])->values(),
            'latestCustomerOrder' => function () use ($request): ?array {
                if (! Auth::guard('customer')->check()) {
                    return null;
                }

                $order = Order::query()
                    ->where('customer_id', Auth::guard('customer')->id())
                    ->when($request->query('order'), fn ($query, $number) => $query->where('number', $number))
                    ->latest('id')
                    ->first();

                return $order ? [
                    'number' => $order->number,
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'status' => $order->status,
                    'total' => (float) $order->total,
                    'fulfillment' => $order->fulfillment,
                    'date' => $order->scheduled_date?->format('Y-m-d'),
                    'time' => $order->scheduled_time,
                    'message' => $order->message,
                    'value' => $order->value !== null ? (float) $order->value : null,
                    'detail' => $order->detail,
                    'phone' => $order->customer->phone,
                    'request' => $order->scheduled_date !== null,
                    'paymentMethod' => $order->payment_method,
                    'deliveryAddress' => $order->delivery_address,
                    'items' => $order->items,
                ] : null;
            },
            'customerOrders' => fn () => Auth::guard('customer')->check()
                ? Order::query()
                    ->where('customer_id', Auth::guard('customer')->id())
                    ->latest('created_at')
                    ->get()
                    ->map(fn (Order $order) => [
                        'number' => $order->number,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                        'phone' => $order->customer->phone,
                        'status' => $order->status,
                        'fulfillment' => $order->fulfillment,
                        'date' => $order->scheduled_date?->format('Y-m-d'),
                        'time' => $order->scheduled_time,
                        'message' => $order->message,
                        'detail' => $order->detail,
                        'request' => $order->scheduled_date !== null,
                        'total' => (float) $order->total,
                        'createdAt' => $order->created_at?->toIso8601String(),
                        'items' => $order->items,
                    ])
                    ->values()
                : [],
            'adminOrders' => fn () => $request->user()
                ? Order::query()
                    ->whereNull('scheduled_date')
                    ->with('customer:id,name,email')
                    ->latest('id')
                    ->get()
                    ->map(fn (Order $order) => [
                        'id' => $order->id,
                        'number' => $order->number,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                        'status' => $order->status,
                        'fulfillment' => $order->fulfillment,
                        'total' => (float) $order->total,
                        'createdAt' => $order->created_at?->toIso8601String(),
                        'items' => $order->items,
                    ])
                    ->values()
                : [],
            'adminReservations' => fn () => $request->user()
                ? Order::query()
                    ->whereNotNull('scheduled_date')
                    ->with('customer:id,name,email,phone')
                    ->latest('id')
                    ->get()
                    ->map(fn (Order $order) => [
                        'number' => $order->number,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                        'phone' => $order->customer->phone,
                        'status' => $order->status,
                        'date' => $order->scheduled_date?->format('Y-m-d'),
                        'time' => $order->scheduled_time,
                        'message' => $order->message,
                        'value' => $order->value !== null ? (float) $order->value : null,
                        'detail' => $order->detail,
                        'createdAt' => $order->created_at?->toIso8601String(),
                    ])
                    ->values()
                : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

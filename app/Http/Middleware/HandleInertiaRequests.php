<?php

namespace App\Http\Middleware;

use App\Models\Product;
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
                'customer' => Auth::guard('customer')->user()?->only(['id', 'name', 'email']),
            ],
            'customerStatus' => fn () => $request->session()->get('customerStatus'),
            'adminStatus' => fn () => $request->session()->get('adminStatus'),
            'catalogProducts' => fn () => Product::query()->latest()->get()->map(fn (Product $product) => [
                'id' => (string) $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'group' => $product->group,
                'price' => (float) $product->price,
                'description' => $product->description,
                'image' => str_starts_with($product->image, 'http') ? $product->image : asset('storage/'.$product->image),
            ])->values(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

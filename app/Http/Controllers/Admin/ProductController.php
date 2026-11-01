<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private const CATEGORIES = ['coffee', 'cupcakes', 'news'];

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $validated['image'] = $request->file('image')->store('products', 'public');

        Product::create($validated);

        return back()->with('adminStatus', 'Produto adicionado ao cardápio.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        } else {
            $validated['image'] = $product->image;
        }

        $product->update($validated);

        return back()->with('adminStatus', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('adminStatus', 'Produto removido do cardápio.');
    }

    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ], [
            'action.in' => 'Informe uma ação de estoque válida.',
            'quantity.required' => 'Informe a quantidade.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser maior que zero.',
            'quantity.max' => 'A quantidade máxima por operação é 1.000.000.',
        ]);

        DB::transaction(function () use ($product, $validated): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $quantity = (int) $validated['quantity'];
            $newStock = $validated['action'] === 'add'
                ? $lockedProduct->stock + $quantity
                : $lockedProduct->stock - $quantity;

            $lockedProduct->update(['stock' => max(0, $newStock)]);
        });

        return back()->with('adminStatus', 'Estoque atualizado com sucesso.');
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $nameRule = Rule::unique('products', 'name');

        if ($product) {
            $nameRule = $nameRule->ignore($product->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:191', $nameRule],
            'description' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'image' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Informe o nome do produto.',
            'name.unique' => 'Já existe um produto com este nome.',
            'name.max' => 'O nome do produto deve ter no máximo 191 caracteres.',
            'description.required' => 'Informe a descrição do produto.',
            'description.max' => 'A descrição deve ter no máximo 150 caracteres.',
            'price.required' => 'Informe o preço do produto.',
            'price.numeric' => 'Informe um preço válido.',
            'price.gt' => 'O preço deve ser maior que zero.',
            'category.required' => 'Selecione uma categoria.',
            'category.in' => 'Selecione uma categoria válida.',
            'image.required' => 'Selecione uma foto para o produto.',
            'image.image' => 'O arquivo selecionado deve ser uma imagem.',
            'image.mimes' => 'A foto deve estar no formato JPG, PNG ou WEBP.',
            'image.max' => 'A foto deve ter no máximo 5 MB.',
        ]);
    }
}

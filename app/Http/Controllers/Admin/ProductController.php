<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'image' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Informe o nome do produto.',
            'name.unique' => 'Já existe um produto com este nome.',
            'name.max' => 'O nome do produto deve ter no máximo 191 caracteres.',
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

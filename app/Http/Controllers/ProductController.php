<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;

class ProductController extends ApiController
{
    public function index(): JsonResponse
    {
        $products = Product::where('status', 1)
            ->where('quantity', '>', 0)
            ->with('category', 'images')
            ->paginate(9);
        $collection = ProductResource::collection($products)->response()->getData();
        return $this->successResponse(
            data: [
                'products' => $collection->data,
                'links' => $collection->links,
                'meta' => $collection->meta,
            ],
        );
    }

    public function show(Product $product): JsonResponse
    {
        return $this->successResponse(
            data: new ProductResource($product->load('category', 'images')),
        );
    }

    public function random(): JsonResponse
    {
        $products = Product::where('status', 1)
            ->where('quantity', '>', 0)
            ->inRandomOrder()
            ->take(4)
            ->get();
        return $this->successResponse(
            data: ProductResource::collection($products->load('category', 'images')),
        );
    }

    public function tab(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'categories' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $categoryIds = explode(',', $request->categories);
        $categories = Category::findOrFail($categoryIds);
        $tabProducts = [];
        foreach ($categories as $category) {
            $products = ProductResource::collection(
                $category->products()->where('status', 1)
                ->where('quantity', '>', 0)
                ->inRandomOrder()
                ->take(3)
                ->get()
            );
            array_push($tabProducts, $products);
        };

        return $this->successResponse(
            data: [
                'tabList' => $categories->pluck('name'),
                'tabPanel' => $tabProducts,
            ],
        );
    }

    public function menu(Request $request): JsonResponse
    {
        $products = Product::where('status', 1)
            ->where('quantity', '>', 0)
            ->with('category', 'images')
            ->filter()
            ->search()
            ->paginate(9);
        $collection = ProductResource::collection($products)->response()->getData();
        return $this->successResponse(
            data: [
                'products' => $collection->data,
                'links' => $collection->links,
                'meta' => $collection->meta,
            ],
        );
    }
}

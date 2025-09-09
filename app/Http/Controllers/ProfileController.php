<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use App\Models\Wishlist;
use App\Models\Transaction;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ProductResource;
use App\Http\Resources\WishlistResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserAddressResource;

class ProfileController extends ApiController
{
    public function provincesCities(): JsonResponse
    {
        return $this->successResponse(
            data: [
                'provinces' => Province::all(),
                'cities' => City::all(),
            ],
        );
    }

    public function indexAddress(Request $request): JsonResponse
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->latest()
            ->get();
        return $this->successResponse(
            data: UserAddressResource::collection($addresses),
        );
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'title' => [
                'required',
                Rule::unique('user_addresses')
                    ->where('user_id', $request->user()->id),
            ],
            'phone' => 'required|unique:user_addresses,phone|regex:/^09[0-3][0-9]{8}$/',
            'postal_code' => 'required|unique:user_addresses,postal_code',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $userAddress = UserAddress::create([
            'title' => $request->title,
            'phone' => $request->phone,
            'postal_code' => $request->postal_code,
            'user_id' => $request->user()->id,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
        ]);
        return $this->successResponse(
            data: new UserAddressResource($userAddress),
            code: 201,
        );
    }

    public function updateAddress(Request $request, UserAddress $userAddress): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'title' => [
                'nullable',
                Rule::unique('user_addresses')
                    ->where('user_id', $request->user()->id)
                    ->ignore($userAddress->id),
            ],
            'phone' => 'nullable|regex:/^09[0-3][0-9]{8}$/|unique:user_addresses,phone,' . $userAddress->id,
            'postal_code' => 'nullable|unique:user_addresses,postal_code,' . $userAddress->id,
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $userAddress->update([
            'title' => $request->filled('title') ? $request->title : $userAddress->title,
            'phone' => $request->filled('phone') ? $request->phone : $userAddress->phone,
            'postal_code' => $request->filled('postal_code') ? $request->postal_code : $userAddress->postal_code,
            'user_id' => $request->user()->id,
            'province_id' => $request->filled('province_id') ? $request->province_id : $userAddress->province_id,
            'city_id' => $request->filled('city_id') ? $request->city_id : $userAddress->city_id,
            'address' => $request->filled('address') ? $request->address : $userAddress->address,
        ]);
        return $this->successResponse(
            data: new UserAddressResource($userAddress),
        );
    }

    public function showAddress(UserAddress $userAddress): JsonResponse
    {
        return $this->successResponse(
            data: new UserAddressResource($userAddress),
        );
    }

    public function destroyAddress(UserAddress $userAddress): JsonResponse
    {
        $userAddress->delete();
        return $this->successResponse(
            data: null,
            message: "Address with id $userAddress->id has been deleted.",
        );
    }

    public function addToWishlist(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $alreadyExistantProduct = Wishlist::where('user_id', auth()->user()->id)
            ->where('product_id', $request->product_id)
            ->first();
        if ($alreadyExistantProduct) {
            return $this->errorResponse(
                message: 'You have already added this product to your wishlist.',
                code: 409,
            );
        };
        $product = Product::findOrFail($request->product_id);
        $wishlist = Wishlist::create([
            'user_id' => auth()->user()->id,
            'product_id' => $product->id,
        ]);
        return $this->successResponse(
            data: new WishlistResource($wishlist->load('product')),
            code: 201,
        );
    }

    public function removeFromWishlist(Wishlist $wishlist): JsonResponse
    {
        $wishlist->delete();
        return $this->successResponse(
            data: null,
            message: 'Product has been removed from the wishlist',
        );
    }

    public function wishlist(): JsonResponse
    {
        $wishlistProducts = Wishlist::where('user_id', auth()->id())
            ->with('product')
            ->latest()
            ->paginate(5);
        $collection = WishlistResource::collection($wishlistProducts)
            ->response()
            ->getData();
        return $this->successResponse(
            data: [
                'products' => $collection->data,
                'links' => $collection->links,
                'meta' => $collection->meta,
            ],
        );
    }

    public function getUser(): JsonResponse
    {
        return $this->successResponse(
            data: new UserResource(auth()->user()),
        );
    }

    public function UpdateUser(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . auth()->user()->id,
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $user = auth()->user();
        $user->update([
            'name' => $request->filled('name') ? $request->name : $user->name,
            'email' => $request->filled('email') ? $request->email : $user->email,
        ]);
        $user->tokens()->delete();
        return $this->successResponse(
            data: new UserResource($user),
            message: "Personal information updated. Please log in again.",
        );
    }

    public function orders(): JsonResponse
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('userAddress', 'orderItems.product')
            ->latest()
            ->paginate(5);
        $collection = OrderResource::collection($orders)->response()->getData();
        return $this->successResponse(
            data: [
                'orders' => $collection->data,
                'links' => $collection->links,
                'meta' => $collection->meta,
            ],
        );
    }

    public function transactions(): JsonResponse
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->latest()
            ->paginate(5);
        $collection = TransactionResource::collection($transactions)->response()->getData();
        return $this->successResponse(
            data: [
                'transactions' => $collection->data,
                'links' => $collection->links,
                'meta' => $collection->meta,
            ],
        );
    }
}

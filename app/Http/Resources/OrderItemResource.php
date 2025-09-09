<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_image' => config('app.path_to_images_admin_panel') . 'products/' . $this->product->primary_image,
            'product_name' => $this->product->name,
            'product_price' => number_format($this->price),
            'product_quantity' => $this->quantity,
            'total_price' => number_format($this->subtotal),
        ];
    }
}

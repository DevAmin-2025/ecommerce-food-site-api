<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'primary_image' => config('app.path_to_images_admin_panel') . 'products/' . $this->primary_image,
            'description' => $this->description,
            'price' => number_format($this->price),
            'quantity' => $this->quantity,
            'status' => $this->status == 1 ? 'Actice' : 'Inactive',
            'is_sale' => $this->is_sale,
            'sale_price' => $this->sale_price ? number_format($this->sale_price) : null,
            'date_on_sale_from_jalali' => $this->date_on_sale_from ?
                verta($this->date_on_sale_from)->formatDatetime() : null,
            'date_on_sale_to_jalali' => $this->date_on_sale_to ?
                verta($this->date_on_sale_to)->formatDatetime() : null,
            'date_on_sale_from_gregorian' => $this->date_on_sale_from,
            'date_on_sale_to_gregorian' => $this->date_on_sale_to,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}

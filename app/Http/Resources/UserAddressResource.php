<?php

namespace App\Http\Resources;

use App\Models\City;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
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
            'title' => $this->title,
            'phone' => $this->phone,
            'postal_code' => $this->postal_code,
            'province_id' => $this->province_id,
            'province_name' => Province::find($this->province_id)->name,
            'city_id' => $this->city_id,
            'city_name' => City::find($this->city_id)->name,
            'address' => $this->address,
        ];
    }
}

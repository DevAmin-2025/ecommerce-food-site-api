<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FooterResource extends JsonResource
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
            'contact_address' => $this->contact_address,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'title' => $this->title,
            'body' => $this->body,
            'work_days' => $this->work_days,
            'work_hour_from' => $this->work_hour_from,
            'work_hour_to' => $this->work_hour_to,
            'telegram_link' => $this->telegram_link,
            'whatsapp_link' => $this->whatsapp_link,
            'instagram_link' => $this->instagram_link,
            'youtube_link' => $this->youtube_link,
            'copyright' => $this->copyright,
        ];
    }
}

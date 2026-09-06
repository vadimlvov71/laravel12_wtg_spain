<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
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
            'supplier' => $this->supplier->code,
            'price' => $this->price,
            'currency' => $this->currency,
            'available_units' => $this->available_units,
            'expires_at' => $this->expires_at->toIso8601String(),
        ];
    }
}

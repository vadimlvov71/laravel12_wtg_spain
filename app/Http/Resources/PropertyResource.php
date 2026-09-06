<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Данные уже полностью подготовлены на уровне БД
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,
            'best_offer' => [
                'id' => $this->offer_id,
                'supplier' => $this->supplier_code,
                'price' => $this->price,
                'currency' => $this->currency,
                'available_units' => $this->available_units,
                'expires_at' => Carbon::parse($this->expires_at)->toIso8601String(),
            ]
        ];
    }
}

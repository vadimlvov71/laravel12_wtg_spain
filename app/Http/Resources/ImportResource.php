<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier' => $this->supplier,
            'external_import_id' => $this->external_import_id,
            'sent_at' => optional($this->sent_at)?->toISOString(),
            'status' => $this->status,
            'total_offers' => $this->total_offers,
            'processed_offers' => $this->processed_offers,
            'error' => $this->error_message,
            'created_at' => optional($this->created_at)?->toISOString(),
            'completed_at' => optional($this->completed_at)?->toISOString(),
        ];
    }
}
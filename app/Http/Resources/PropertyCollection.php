<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\PropertyResource;

class PropertyCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PropertyResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
                'next' => $this->resource->nextPageUrl(),
                'prev' => $this->resource->previousPageUrl(),
            ]
        ];
    }
    /**
     * Переопределяем метод для отключения оборачивания в "data"
     */
    public function with($request)
    {
        return [];
    }

    public function withResponse(Request $request, JsonResponse $response): JsonResponse
    {
        // Убираем meta и links из ответа
        $data = $response->getData(true);
        
        if (isset($data['meta'])) {
            unset($data['meta']);
        }
        if (isset($data['links'])) {
            unset($data['links']);
        }
        
        return $response->setData($data);
    }
}

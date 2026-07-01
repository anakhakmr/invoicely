<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Invoice $resource
 */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'invoice_number' => $this->resource->invoice_number,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'total' => (float) $this->resource->total,
            'due_date' => $this->resource->due_date->toDateString(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'items' => $this->whenLoaded('items', fn () => $this->resource->items->map(fn (InvoiceItem $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}

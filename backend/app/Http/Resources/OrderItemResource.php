<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->product_id,
            'product_name'   => $this->product_name,
            'unit_price'     => (float) $this->unit_price,
            'options_price'  => (float) $this->options_price,
            'quantity'       => $this->quantity,
            'selected_options' => $this->selected_options,
            'note'           => $this->note,
        ];
    }
}

<?php

namespace Modules\Printer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material' => 'required|string|max:255',
            'color_name' => 'required|string|max:255',
            'color_hex' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'diameter_mm' => 'nullable|numeric|min:0',
            'total_weight_g' => 'nullable|integer|min:1',
            'remaining_g' => 'nullable|integer|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_store' => 'nullable|string|max:255',
            'purchased_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}

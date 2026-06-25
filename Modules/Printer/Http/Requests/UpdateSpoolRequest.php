<?php

namespace Modules\Printer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material' => 'sometimes|required|string|max:255',
            'color_name' => 'sometimes|required|string|max:255',
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand' => 'nullable|string|max:255',
            'diameter_mm' => 'nullable|numeric|min:0',
            'total_weight_g' => 'sometimes|required|integer|min:1',
            'remaining_g' => 'sometimes|required|integer|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_store' => 'nullable|string|max:255',
            'purchased_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}

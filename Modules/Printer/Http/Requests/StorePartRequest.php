<?php

namespace Modules\Printer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['spare', 'consumable'])],
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:255',
            'low_threshold' => 'nullable|integer|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_store' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}

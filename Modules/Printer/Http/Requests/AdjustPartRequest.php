<?php

namespace Modules\Printer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delta' => 'required|numeric',
        ];
    }
}

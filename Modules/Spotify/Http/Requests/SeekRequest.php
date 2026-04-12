<?php

namespace Modules\Spotify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_ms' => 'required|integer|min:0',
        ];
    }
}

<?php

namespace Modules\Spotify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetShuffleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'state' => 'required|boolean',
        ];
    }
}

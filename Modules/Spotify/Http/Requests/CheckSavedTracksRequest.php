<?php

namespace Modules\Spotify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckSavedTracksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|max:50',
            'ids.*' => 'string|max:50',
        ];
    }
}

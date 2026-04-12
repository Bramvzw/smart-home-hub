<?php

namespace Modules\Spotify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSaveTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'    => 'required|string|max:50',
            'saved' => 'required|boolean',
        ];
    }
}

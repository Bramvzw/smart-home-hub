<?php

namespace Modules\Spotify\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uri' => ['nullable', 'string', 'regex:/^spotify:(track|album|playlist|artist|show|episode):[a-zA-Z0-9]{22}$/'],
        ];
    }
}

<?php

namespace Modules\Lighting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'power' => ['sometimes', 'boolean'],
            'brightness' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'color' => ['sometimes', 'string', 'regex:/^#?[0-9a-fA-F]{6}$/'],
        ];
    }

    /**
     * The validated changes, with the colour normalised to a leading-hash hex.
     *
     * @return array{power?: bool, brightness?: int, color?: string}
     */
    public function changes(): array
    {
        $changes = $this->validated();

        if (isset($changes['color']) && ! str_starts_with($changes['color'], '#')) {
            $changes['color'] = '#'.$changes['color'];
        }

        return $changes;
    }
}

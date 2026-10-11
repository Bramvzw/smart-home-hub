<?php

namespace Modules\Lighting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ControlLightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lights' => ['required', 'array', 'min:1', 'max:50'],
            'lights.*.provider' => ['required', 'string', 'max:32'],
            'lights.*.id' => ['required', 'string', 'max:128'],
            'changes' => ['required', 'array:power,brightness,color,color_temp'],
            'changes.power' => ['sometimes', 'boolean'],
            'changes.brightness' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'changes.color' => ['sometimes', 'string', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'changes.color_temp' => ['sometimes', 'integer', 'min:0', 'max:100', 'prohibits:changes.color'],
        ];
    }

    /**
     * @return list<array{provider: string, id: string}>
     */
    public function lights(): array
    {
        return array_values(array_map(
            static fn (array $light): array => ['provider' => (string) $light['provider'], 'id' => (string) $light['id']],
            (array) $this->validated('lights'),
        ));
    }

    /**
     * The validated changes, with the colour normalised to a leading-hash hex.
     *
     * @return array{power?: bool, brightness?: int, color?: string, color_temp?: int}
     */
    public function changes(): array
    {
        $changes = (array) $this->validated('changes');

        if (isset($changes['power'])) {
            $changes['power'] = (bool) $changes['power'];
        }
        if (isset($changes['color']) && ! str_starts_with((string) $changes['color'], '#')) {
            $changes['color'] = '#'.$changes['color'];
        }

        return $changes;
    }
}

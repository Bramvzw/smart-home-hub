<?php

namespace Modules\Lighting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLightingSceneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:60'],
            'accent' => ['sometimes', 'required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // Given lamps are re-captured from their current state ("Update to current").
            'lights' => ['sometimes', 'array', 'min:1', 'max:50'],
            'lights.*.provider' => ['required', 'string', 'max:32'],
            'lights.*.id' => ['required', 'string', 'max:128'],
        ];
    }

    public function name(): ?string
    {
        return $this->has('name') ? (string) $this->validated('name') : null;
    }

    public function accent(): ?string
    {
        return $this->has('accent') ? (string) $this->validated('accent') : null;
    }

    /**
     * @return list<array{provider: string, id: string}>|null
     */
    public function lights(): ?array
    {
        if (! $this->has('lights')) {
            return null;
        }

        return array_values(array_map(
            static fn (array $light): array => ['provider' => (string) $light['provider'], 'id' => (string) $light['id']],
            (array) $this->validated('lights'),
        ));
    }
}

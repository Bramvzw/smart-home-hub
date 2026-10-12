<?php

namespace Modules\Lighting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLightingSceneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'lights' => ['required', 'array', 'min:1', 'max:50'],
            'lights.*.provider' => ['required', 'string', 'max:32'],
            'lights.*.id' => ['required', 'string', 'max:128'],
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
}

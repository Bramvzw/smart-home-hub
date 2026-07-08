<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleStatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'modules' => 'required|array',
            'modules.*.enabled' => 'required|boolean',
            'modules.*.order' => 'required|integer|min:0|max:99',
        ];
    }
}

<?php

namespace Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:160',
            'description' => 'nullable|string|max:4000',
            'priority' => 'sometimes|required|in:low,normal,high',
            'due_date' => 'nullable|date',
            'completed' => 'sometimes|boolean',
            'labels' => 'sometimes|array',
            'labels.*.id' => 'nullable|integer|exists:task_labels,id',
            'labels.*.name' => 'nullable|string|max:40',
            'labels.*.color' => 'nullable|string|max:20',
            'checklist' => 'sometimes|array',
            'checklist.*.id' => 'nullable|integer',
            'checklist.*.text' => 'required_with:checklist|string|max:180',
            'checklist.*.completed' => 'boolean',
        ];
    }
}

<?php

namespace Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'column_id' => 'required|exists:task_columns,id',
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer|exists:kanban_tasks,id',
        ];
    }
}

<?php

namespace Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'board_id' => 'nullable|integer|exists:task_boards,id',
            'type' => 'required|in:habit,maintenance',
            'title' => 'required|string|max:160',
            'description' => 'nullable|string|max:4000',
            'cadence_type' => 'required|in:times_per_week,weekdays,weekly,monthly,interval,annual',
            'cadence_config' => 'sometimes|array',
            'notify' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'next_due_on' => 'nullable|date',
        ];
    }
}

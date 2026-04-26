<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255', function ($attribute, $value, $fail) {
        if ($value !== strip_tags($value)) {
            $fail('The title contains forbidden HTML tags.');
        }
    },
    ],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority'    => ['sometimes', Rule::in(TaskPriority::values())],
            'due_date'    => ['nullable', 'date'],
            'status'      => ['sometimes', Rule::in(TaskStatus::values())],
        ];
    }
}

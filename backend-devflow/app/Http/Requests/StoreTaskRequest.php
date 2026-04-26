<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TaskPriority;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title'       => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
        if ($value !== strip_tags($value)) {
            $fail('HTML tags are not allowed.');
        }
    },],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority'    => ['required', Rule::in(TaskPriority::values())],
            'due_date'    => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages():array
    {
        return [
            'title.required'       => 'Task title is required.',
            'priority.in'          => 'Priority must be low, medium, or high.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ];
    }
}

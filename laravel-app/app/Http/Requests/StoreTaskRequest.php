<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タスクのタイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'status.in' => 'ステータスは pending, in_progress, completed, cancelled のいずれかである必要があります。',
            'priority.in' => '優先度は low, medium, high, urgent のいずれかである必要があります。',
            'due_date.date' => '期限は有効な日付形式である必要があります。',
            'due_date.after' => '期限は現在より後の日時を指定してください。',
        ];
    }
}

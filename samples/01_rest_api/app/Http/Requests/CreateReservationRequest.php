<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
{
    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', 'max:255'],
            'resource_name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    /**
     * カスタムメッセージ
     */
    public function messages(): array
    {
        return [
            'user_name.required' => '利用者名は必須です',
            'resource_name.required' => 'リソース名は必須です',
            'starts_at.required' => '開始時刻は必須です',
            'starts_at.after' => '開始時刻は現在時刻より後である必要があります',
            'ends_at.required' => '終了時刻は必須です',
            'ends_at.after' => '終了時刻は開始時刻より後である必要があります',
        ];
    }
}


<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос на обновление настроек.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Для простоты, в реальном проекте проверить авторизацию
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cbr_fetch_currencies' => 'sometimes|array',
            'cbr_fetch_currencies.*' => 'string|size:3',
            'widget_currencies' => 'sometimes|array',
            'widget_currencies.*' => 'string|size:3',
            'widget_update_interval' => 'sometimes|integer|min:10|max:3600',
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
            'cbr_fetch_currencies.array' => 'cbr_fetch_currencies должно быть массивом.',
            'cbr_fetch_currencies.*.string' => 'Каждый код валюты должен быть строкой.',
            'cbr_fetch_currencies.*.size' => 'Код валюты должен состоять из 3 символов.',
            'widget_currencies.array' => 'widget_currencies должно быть массивом.',
            'widget_currencies.*.string' => 'Каждый код валюты должен быть строкой.',
            'widget_currencies.*.size' => 'Код валюты должен состоять из 3 символов.',
            'widget_update_interval.integer' => 'Интервал обновления должен быть целым числом.',
            'widget_update_interval.min' => 'Интервал обновления должен быть не менее 10 секунд.',
            'widget_update_interval.max' => 'Интервал обновления должен быть не более 3600 секунд.',
        ];
    }
}

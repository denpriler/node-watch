<?php

namespace App\Http\Requests\Monitor;

use Illuminate\Foundation\Http\FormRequest;

class GetMonitorLogsRequest extends FormRequest
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
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'from' => 'sometimes|date_format:Y-m-d',
            'to' => 'sometimes|date_format:Y-m-d|after_or_equal:from',
        ];
    }
}

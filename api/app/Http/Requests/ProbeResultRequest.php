<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProbeResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'monitor_id' => ['required', 'integer', 'exists:monitors,id'],
            'region' => ['required', 'string', 'max:50'],
            'status_code' => ['required', 'integer', 'min:0', 'max:599'],
            'response_time_ms' => ['required', 'integer', 'min:0'],
            'ttfb_ms' => ['required', 'integer', 'min:0'],
            'is_up' => ['required', 'boolean'],
            'error' => ['nullable', 'string', 'max:1000'],
            'checked_at' => ['required', 'date'],
        ];
    }
}

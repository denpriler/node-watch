<?php

namespace App\Http\Requests\Monitor;

use App\Enum\Monitor\MonitorMethod;
use App\Enum\Monitor\MonitorRegion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

class UpdateMonitorRequest extends FormRequest
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
     * @return array<string, ValidationRule|Enum|In|list<ValidationRule|Enum|In|string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:3|max:255',
            'method' => ['sometimes', Rule::enum(MonitorMethod::class)],
            'check_interval' => ['sometimes', 'numeric', Rule::in([30, 60, 120, 180, 240, 360])],
            'timeout' => 'sometimes|numeric|min:5|max:60',
            'expected_status' => 'sometimes|numeric|min:200',
            'regions' => 'sometimes|array',
            'regions.*' => [Rule::enum(MonitorRegion::class)],
            'is_active' => 'sometimes|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests\Monitor;

use App\Enum\Monitor\MonitorMethod;
use App\Enum\Monitor\MonitorRegion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

class CreateMonitorRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:255',
            'url' => 'required|active_url',
            'method' => ['required', Rule::enum(MonitorMethod::class)],
            'check_interval' => ['required', 'numeric', Rule::in([30, 60, 120, 180, 240, 360])],
            'timeout' => 'required|numeric|min:5|max:60',
            'expected_status' => 'required|numeric|min:200',
            'regions' => 'required|array',
            'regions.*' => ['required', Rule::enum(MonitorRegion::class)],
            'is_active' => 'required|boolean',
        ];
    }
}

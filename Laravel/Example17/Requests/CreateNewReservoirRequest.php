<?php

namespace App\Http\Requests\Admin;

use App\Rules\GroupBelongsToCompanyFromRequest;
use App\Rules\GroupIsCompany;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateNewReservoirRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', new GroupIsCompany],
            'group_id' => ['required', 'integer', new GroupBelongsToCompanyFromRequest],
            'name' => ['required', 'min:1', 'max:255'],
            'device_id' => ['required', 'min:1', 'max:255'],
            'device_code' => ['required', 'min:1', 'max:255'],
            'device_type' => ['nullable', 'min:1', 'max:255'],
            'serial_no' => ['required', 'min:1', 'max:255'],
            'longitude' => ['required', 'min:1', 'max:255'],
            'latitude' => ['required', 'min:1', 'max:255'],
            'file_name' => ['required', 'min:1', 'max:255'],
            'width' => ['required', 'min:0'],
            'max_height' => ['required', 'min:0'],
        ];
    }
}

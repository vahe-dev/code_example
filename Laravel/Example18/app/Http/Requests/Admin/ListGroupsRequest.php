<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListGroupsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['integer', 'min:1', 'max:10000'],
            'limit' => ['integer', 'min:1', 'max:10000'],
            'company_id' => ['nullable', 'exists:groups,id'],
        ];
    }
}

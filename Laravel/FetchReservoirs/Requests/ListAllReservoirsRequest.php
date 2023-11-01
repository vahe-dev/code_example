<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAllReservoirsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'limit' => ['integer', 'min:1', 'max:10000'],
            'company_id' => ['nullable', 'exists:groups,id']
        ];
    }
}

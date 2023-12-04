<?php

namespace Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'min:1', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'status'   => ['integer', 'min:0', 'max:1'],
            'remember_token'    => ['string'],
        ];
    }
}

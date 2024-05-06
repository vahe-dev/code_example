<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubGroupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:1', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:groups,id'],
            'is_sub_group' => ['nullable', 'integer', 'exists:groups,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['required', 'numeric', 'exists:users,id'],
        ];
    }
}

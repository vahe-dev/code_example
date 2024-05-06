<?php

namespace App\Rules;

use App\Models\Group;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class GroupBelongsToCompanyFromRequest implements ValidationRule, DataAwareRule
{
    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = Group::find($value);

        if(is_null($group)) {
            $fail("Group with given :attribute doesn't exist");
        }
        else if($group['is_sub_group'] == 0) {
            if ($group->parent_id !== $this->data['company_id']) {
                $fail("Given :attribute doesn't not belong to a given company");
            }
        }
        else if($group['is_sub_group'] == 1) {
            if ($group->parent_id !== $this->data['groupId']) {
                $fail("Given :attribute doesn't not belong to a given company");
            }
        }
    }

    public function setData(array $data): GroupBelongsToCompanyFromRequest|static
    {
        $this->data = $data;

        return $this;
    }
}

<?php

namespace App\Actions\SubGroups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateSubGroup
{
    use AsAction;

    public function handle(array $input): Group
    {
        return Group::create([
            'parent_id' => $input['group_id'],
            'is_sub_group' => 1,
            'name' => $input['name'],
            'is_company' => 0,
        ]);
    }
}

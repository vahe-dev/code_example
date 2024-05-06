<?php

namespace App\Actions\SubGroups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateSubGroup
{
    use AsAction;

    public function handle(Group $group, array $input)
    {
        $group->fill($input);
        $group->parent_id = $input['group_id'];
        $group->save();
        return $group;
    }
}

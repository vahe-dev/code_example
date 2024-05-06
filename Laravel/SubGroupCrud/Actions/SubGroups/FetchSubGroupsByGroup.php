<?php

namespace App\Actions\SubGroups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchSubGroupsByGroup
{
    use AsAction;

    public function handle(int $groupId)
    {
        return Group::whereIsCompany(false)
            ->where('parent_id', $groupId)
            ->where('is_sub_group', 1)
            ->select('id', 'parent_id', 'name')
            ->get();
    }
}

<?php

namespace App\Actions\SubGroups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class BulkDeleteSubGroups
{
    use AsAction;

    public function handle(array $ids)
    {
        return Group::whereIn('id', $ids)->delete();
    }
}

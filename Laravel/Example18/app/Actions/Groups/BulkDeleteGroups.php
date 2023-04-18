<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class BulkDeleteGroups
{
    use AsAction;

    public function handle(array $ids)
    {
        return Group::whereIn('id', $ids)->delete();
    }
}

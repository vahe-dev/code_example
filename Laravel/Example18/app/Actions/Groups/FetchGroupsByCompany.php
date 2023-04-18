<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchGroupsByCompany
{
    use AsAction;

    public function handle(int $companyId)
    {
        return Group::whereIsCompany(false)
            ->where('parent_id', $companyId)
            ->select('id', 'parent_id', 'name')
            ->get();
    }
}

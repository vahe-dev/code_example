<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class BulkDeleteCompanies
{
    use AsAction;

    public function handle(array $ids)
    {
        return Group::whereIn('id', $ids)->delete();
    }
}

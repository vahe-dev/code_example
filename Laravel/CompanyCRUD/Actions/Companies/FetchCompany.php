<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchCompany
{
    use AsAction;

    /**
     * @param int $companyId
     * @return Group|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function handle(int $companyId)
    {
        return Group::whereIsCompany(true)->where('id', '=', $companyId)->first();
    }
}

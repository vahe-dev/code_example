<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Collection;

class FetchCompanies
{
    use AsAction;

    public function handle(): Collection
    {
        return Group::whereIsCompany(true)->select('id', 'name')->get();
    }
}

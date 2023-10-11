<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CompaniesWithGroupsCount
{
    use AsAction;

    public function handle()
    {
        return Group::select(
            'companies.id as id',
            'companies.name as companies_name',
            DB::raw('COUNT(DISTINCT groups.id) as groups_count'),
            DB::raw('COUNT(DISTINCT reservoirs.id) as reservoirs_count'),
            'companies.status'
        )
            ->from('groups as companies')
            ->leftJoin('groups as groups', 'companies.id', '=', 'groups.parent_id')
            ->leftJoin('reservoirs', 'groups.id', '=', 'reservoirs.group_id')
            ->where('companies.is_company', 1)
            ->groupBy('companies.id')
            ->get();
    }
}

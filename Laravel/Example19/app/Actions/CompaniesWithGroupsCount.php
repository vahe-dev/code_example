<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CompaniesWithGroupsCount
{
    use AsAction;

    /**
     * Get all companies with child groups counts
     *
     * */
    public function handle()
    {
        return Group::leftJoin('groups AS g2', function (JoinClause $join) {
            $join->on('groups.id', '=', 'g2.parent_id')
                ->where('g2.is_company', '=', 0);
        })
            ->select('groups.id', 'groups.name', DB::raw('COUNT(g2.id) AS groups_count'))
            ->where('groups.is_company', 1)
            ->whereNull('groups.parent_id')
            ->groupBy('groups.id')
            ->orderBy('id', 'desc')
            ->get();
    }
}
<?php

namespace App\Actions\SubGroups;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchSubGroup
{
    use AsAction;

    /**
     * @param int $groupId
     * @return Builder|Model|object|null
     */
    public function handle(int $groupId)
    {
        return Group::leftJoin('groups AS g2', function (JoinClause $join) {
                $join->on('groups.parent_id', '=', 'g2.id')
                    ->where('g2.is_company', '=', 0);
            })
            ->leftJoin('groups AS g3', function (JoinClause $join) {
                $join->on('g2.parent_id', '=', 'g3.id')
                    ->where('g3.is_company', '=', 1);
            })
            ->leftJoin('reservoirs', 'groups.id', '=', 'reservoirs.group_id')
            ->with(['users' => function ($q) {
                $q->select('users.id', 'name', 'username');
            }])
            ->select('groups.id',
                'groups.name',
                'groups.parent_id AS group_id',
                DB::raw('g2.name AS groupName'),
                'g3.id AS company_id',
                DB::raw('g3.name AS companyName')
            )
            ->where('groups.is_sub_group', 1)
            ->where('groups.id', '=', $groupId)
            ->first();
    }
}

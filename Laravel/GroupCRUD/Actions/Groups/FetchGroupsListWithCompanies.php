<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchGroupsListWithCompanies
{
    use AsAction;

    public function handle(array $input)
    {
        $query = Group::leftJoin('groups AS g2', function (JoinClause $join) {
            $join->on('groups.parent_id', '=', 'g2.id')
                ->where('g2.is_company', '=', 1);
        })
            ->leftJoin('reservoirs', 'groups.id', '=', 'reservoirs.group_id')
            ->with(['users' => function($q) {
                $q->select('users.id', 'name', 'username');
            }])

            ->select('groups.id', 'groups.parent_id', 'groups.name', DB::raw('g2.name AS companyName'))
            ->selectRaw('COUNT(reservoirs.id) AS reservoirsCount')
            ->where('groups.is_company', 0)
            ->groupBy('groups.id', 'groups.parent_id', 'groups.name', 'companyName')
            ->orderBy('groups.id', 'desc');

        $search = $input['search'] ?? null;
        $query->when($search, function ($q) use($search) {
            $q->where('groups.name', 'like', "%$search%");
        });

        $companyId = $input['company_id'] ?? null;
        $query->when($companyId, function ($q) use($companyId){
            $q->where('groups.parent_id', '=', $companyId);
        });

        return $query->paginate($input['limit'] ?? 100);
    }
}

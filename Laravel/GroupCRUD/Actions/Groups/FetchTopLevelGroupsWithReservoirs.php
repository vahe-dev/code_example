<?php

namespace App\Actions\Groups;

use App\Enums\Role;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchTopLevelGroupsWithReservoirs
{
    use AsAction;

    public function handle()
    {
        $toplevelGroupsWithReservoirs = Group::with([
            'childrenGroups' => function($query) {
                $query->with(['reservoirs' => function ($query) {
                    $query->select('reservoirs.*', 'groups.parent_id AS companyId')
                        ->join('groups', 'groups.id', '=', 'reservoirs.group_id');

                    if (Auth::user()->username !== 'arazen') {
                        $query->where('reservoirs.status', '=', 1);
                    }
                }]);
                if (Auth::user()->hasRole(Role::ADMIN->value)) {
                    return $query;
                }
                $query->whereHas('mapping', function ($query) {
                    $query->when(Auth::user()->hasRole(Role::USER->value), fn ($query) => $query->whereUserId(Auth::id()));
                });
            }
        ])
            ->whereNull('parent_id')
            ->where(function ($query) {
                if (Auth::user()->username != 'arazen') {
                    $query->where('status', 1);
                }
            })
            ->get();

        $toplevelGroupsWithReservoirs = $toplevelGroupsWithReservoirs->filter(function ($item) {
            return $item->childrenGroups->count() > 0;
        });

        return (object) $toplevelGroupsWithReservoirs->toArray();
    }
}

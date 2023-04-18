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
                if (Auth::user()->hasRole(Role::ADMIN->value)) {
                    return $query;
                }
                $query->whereHas('mapping', function ($query) {
                    $query->when(Auth::user()->hasRole(Role::USER->value), fn ($query) => $query->whereUserId(Auth::id()));
                });
            },
            'childrenGroups.reservoirs',
        ])
            ->whereNull('parent_id')
            ->get();

        return $toplevelGroupsWithReservoirs->filter(function ($item) {
            return $item->childrenGroups->count() > 0;
        });
    }
}

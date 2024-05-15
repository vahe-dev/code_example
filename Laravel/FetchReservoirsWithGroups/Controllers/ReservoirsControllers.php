<?php

namespace App\Actions\Groups;

use App\Enums\Role;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class ReservoirsControllers
{
    public function index(): object
    {
        $parentGroupIds = Auth::user()->groups()->pluck('groups.id');

        // Determine accessible groups for the user based on their roles and permissions
        $accessibleGroupIds = $this->getAccessibleGroupIds();

        // Main query to fetch top-level groups with nested relations
        $toplevelGroupsWithReservoirs = Group::with([
            'childrenGroups' => function ($query) use ($accessibleGroupIds, $parentGroupIds) {
                $query->with([
                    'childrenGroups' => function ($query) use ($parentGroupIds) {
                        $query->with(['reservoirs' => function ($query) {
                            $this->applyReservoirFilters($query);
                        }]);

                        if (Auth::user()->hasRole(Role::USER->value)) {
                            $query->when($parentGroupIds, function ($query) use ($parentGroupIds) {
                                $query->whereIn('groups.id', $parentGroupIds);
                            });
                        }
                    },
                    'reservoirs' => function ($query) {
                        $this->applyReservoirFilters($query);
                    },
                ]);

                // Filter child groups if specific group IDs are accessible
                $query->when($accessibleGroupIds, function ($query) use ($accessibleGroupIds) {
                    $query->whereIn('groups.id', $accessibleGroupIds);
                });
            },
            'reservoirs' => function ($query) {
                $this->applyReservoirFilters($query);
            }
        ])
            ->whereNull('parent_id')
            ->where(function ($query) {
                $this->applyStatusFilter($query);
            })
            ->get();

        // Optionally, filter out top-level groups without any children groups
        $toplevelGroupsWithReservoirs = $toplevelGroupsWithReservoirs->filter(function ($group) {
            return $group->childrenGroups->isNotEmpty();
        });

        return (object) $toplevelGroupsWithReservoirs->toArray();
    }

    protected function getAccessibleGroupIds()
    {
        if (Auth::user()->hasRole(Role::USER->value)) {
            $parentGroupIds = Auth::user()->groups()->pluck('groups.id');
            $childParentIds = Group::whereIn('id', $parentGroupIds)->pluck('parent_id');
            $childParent2Ids = Group::whereIn('id', $childParentIds)->whereNotNull('parent_id')->pluck('parent_id');
            return $parentGroupIds->merge($childParentIds)->merge($childParent2Ids);
        }

        return null;  // Admins have access to all groups by default
    }

    protected function applyReservoirFilters($query): void
    {
        $query->select('reservoirs.*', 'groups.parent_id AS companyId')
            ->join('groups', 'groups.id', '=', 'reservoirs.group_id');

        if (Auth::user()->username !== 'admin') {
            $query->where('reservoirs.status', '=', 1);
        }
    }

    protected function applyStatusFilter($query): void
    {
        if (Auth::user()->username != 'admin') {
            $query->where('status', 1);
        }
    }
}

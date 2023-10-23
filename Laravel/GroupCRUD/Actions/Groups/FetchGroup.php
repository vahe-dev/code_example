<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchGroup
{
    use AsAction;

    /**
     * @param int $groupId
     * @return Group|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function handle(int $groupId)
    {
        return Group::with(['users' => function($q) {
                $q->select('users.id', 'name');
            }])->with(['company' => function($q) {
                $q->select('id', 'name');
            }])
            ->whereIsCompany(false)
            ->where('id', '=', $groupId)->first();
    }
}

<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class BulkDeleteUsers
{
    use AsAction;

    /**
     * Delete multiple user records by their IDs.
     *
     * @param  array  $ids  The array of user IDs to delete.
     * @return int  The number of deleted user records.
     */
    public function handle(array $ids)
    {
        return User::whereIn('id', $ids)->delete();
    }
}

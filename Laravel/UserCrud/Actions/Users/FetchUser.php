<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchUser
{
    use AsAction;

    /**
     * @param int $userId
     * @return User|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function handle(int $userId)
    {
        return User::where('id', '=', $userId)->first();
    }
}

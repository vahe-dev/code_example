<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class FetchUsers
{
    use AsAction;

    public function handle()
    {
        return User::get();
    }
}

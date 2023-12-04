<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateUser
{
    use AsAction;

    /**
     * Handles the creation of a new User.
     *
     * @param array $input The input data containing the username and password.
     * @return User The newly created User.
     */
    public function handle(array $input): User
    {
        $userCredential = [
            'username' => $input['username'],
            'password' => bcrypt($input['password']),
            'status' => $input['status'],
        ];
        return User::factory()->create($userCredential);
    }
}

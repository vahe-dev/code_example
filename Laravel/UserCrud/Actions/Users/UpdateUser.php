<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Str;

class UpdateUser
{
    use AsAction;

    /**
     * Update the user with the given input.
     *
     * @param User $user The user to be updated.
     * @param array $input The input data.
     * @return User The updated user.
     */
    public function handle(User $user, array $input)
    {
        $updatedUser = [
            'username' => $input['username'],
            'status' => $input['status']
        ];

        // Check if a new password is provided
        if (!empty($input['password'])) {
            $updatedUser['password'] = bcrypt($input['password']);
            $updatedUser['remember_token'] = Str::random(10);
        }

        $user->update($updatedUser);

        return $user;
    }
}

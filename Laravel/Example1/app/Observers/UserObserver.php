<?php

namespace App\Observers;

use App\Actions\Common\RemoveFile;
use App\Models\Team;
use App\Models\User;
use Laravel\Jetstream\Features;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user): void
    {
        if (! Features::hasTeamFeatures()) {
            return;
        }

        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user): void
    {
        // Need to remove old photo file
        $oldPhotoPath = $user->getOriginal('profile_photo_path');
        if ($user->profile_photo_path && $oldPhotoPath && $user->profile_photo_path !== $oldPhotoPath) {
            RemoveFile::run($oldPhotoPath);
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user): void
    {
        if (!empty($user->profile_photo_path)) {
            RemoveFile::run($user->profile_photo_path);
        }
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}

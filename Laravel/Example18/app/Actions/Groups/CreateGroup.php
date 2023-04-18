<?php

namespace App\Actions\Groups;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateGroup
{
    use AsAction;

    public function handle(array $input): Group
    {
        return Group::create([
            'parent_id' => $input['parent_id'],
            'name' => $input['name'],
            'is_company' => 0,
        ]);
    }
}

<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateCompany
{
    use AsAction;

    public function handle(array $input): Group
    {
        return Group::create([
            'parent_id' => null,
            'name' => $input['name'],
            'is_company' => 1,
            'status' => $input['status'],
        ]);
    }
}

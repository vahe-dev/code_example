<?php

namespace App\Actions\Companies;

use App\Models\Group;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateCompany
{
    use AsAction;

    public function handle(Group $company, array $input)
    {
        $company->update($input);
        return $company;
    }
}

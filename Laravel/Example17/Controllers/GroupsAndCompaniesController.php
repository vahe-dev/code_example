<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupsAndCompaniesController extends Controller
{
    /**
     * Get all groups by company id.
     *
     */
    public function listByCompany(int $companyId): Collection|array
    {
        return Group::whereIsCompany(false)
            ->where('parent_id', $companyId)
            ->select('id', 'parent_id', 'name')
            ->get();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SubGroups\BulkDeleteSubGroups;
use App\Actions\SubGroups\CreateSubGroup;
use App\Actions\SubGroups\FetchSubGroup;
use App\Actions\SubGroups\FetchSubGroupsByGroup;
use App\Actions\SubGroups\FetchSubGroupsListWithCompanies;
use App\Actions\SubGroups\UpdateSubGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteSubGroupsRequest;
use App\Http\Requests\Admin\ListSubGroupsRequest;
use App\Http\Requests\Admin\StoreSubGroupRequest;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class SubGroupsController extends Controller
{
    /**
     * Get all companies
     *
     * @return JsonResponse
     */
    public function index(ListSubGroupsRequest $groupsRequest): JsonResponse
    {
        return $this->handleResponse(FetchSubGroupsListWithCompanies::run($groupsRequest->validated()), __('Fetched all subgroups with companies'));
    }

    /**
     * Create new company.
     *
     * @param StoreSubGroupRequest $request
     * @return JsonResponse
     */
    public function store(StoreSubGroupRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $group = CreateSubGroup::run($validatedData);

        if (!empty($validatedData['user_ids'])) {
            $group->users()->attach($validatedData['user_ids']);
        }

        return $this->handleResponse($group, __('Created the subgroup'));
    }

    /**
     * Get specific company by ID.
     * @param int $companyId
     * @return JsonResponse
     */
    public function show(int $subgroupId): JsonResponse
    {
        return $this->handleResponse(FetchSubGroup::run($subgroupId), __('Fetch the subgroup with attached users'));
    }

    /**
     * @param StoreSubGroupRequest $request
     * @param Group $group
     * @return JsonResponse
     */
    public function update(StoreSubGroupRequest $request, Group $group): JsonResponse
    {
        $validatedData = $request->validated();
        $updated = UpdateSubGroup::run($group, $validatedData);

        $updated->users()->sync($validatedData['user_ids'] ?? []);

        return $this->handleResponse($updated, __('Updated the subgroup'));
    }

    /**
     * Bulk delete companies with given IDs.
     *
     * @param BulkDeleteSubGroupsRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteSubGroupsRequest $request): JsonResponse
    {
        BulkDeleteSubGroups::run($request->ids);
        return $this->handleResponse(null, __('Bulk delete the subgroups success'), 204);
    }
    /**
     * Get list of companies by company ID.
     * @param int $companyId
     * @return JsonResponse
     */
    public function listByGroup(int $groupId): JsonResponse
    {
        return $this->handleResponse(FetchSubGroupsByGroup::run($groupId), __('Fetch subgroups by group'));
    }
}

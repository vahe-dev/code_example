<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Groups\BulkDeleteGroups;
use App\Actions\Groups\CreateGroup;
use App\Actions\Groups\FetchGroup;
use App\Actions\Groups\FetchGroupsByCompany;
use App\Actions\Groups\FetchGroupsListWithCompanies;
use App\Actions\Groups\UpdateGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteGroupsRequest;
use App\Http\Requests\Admin\ListGroupsRequest;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class GroupsController extends Controller
{
    /**
     * Get all companies
     *
     * @return JsonResponse
     */
    public function index(ListGroupsRequest $groupsRequest): JsonResponse
    {
        return $this->handleResponse(FetchGroupsListWithCompanies::run($groupsRequest->validated()), __('Fetched all groups with companies'));
    }

    /**
     * Create new company.
     *
     * @param StoreGroupRequest $request
     * @return JsonResponse
     */
    public function store(StoreGroupRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $group = CreateGroup::run($validatedData);

        if (!empty($validatedData['user_ids'])) {
            $group->users()->attach($validatedData['user_ids']);
        }

        return $this->handleResponse($group, __('Created the group'));
    }

    /**
     * Get specific company by ID.
     * @param int $companyId
     * @return JsonResponse
     */
    public function show(int $companyId): JsonResponse
    {
        return $this->handleResponse(FetchGroup::run($companyId), __('Fetch the group with attached users'));
    }

    /**
     * @param StoreGroupRequest $request
     * @param Group $group
     * @return JsonResponse
     */
    public function update(StoreGroupRequest $request, Group $group): JsonResponse
    {
        $validatedData = $request->validated();
        $updated = UpdateGroup::run($group, $validatedData);

        $updated->users()->sync($validatedData['user_ids'] ?? []);

        return $this->handleResponse($updated, __('Updated the group'));
    }

    /**
     * Bulk delete companies with given IDs.
     *
     * @param BulkDeleteGroupsRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteGroupsRequest $request): JsonResponse
    {
        BulkDeleteGroups::run($request->ids);
        return $this->handleResponse(null, __('Bulk delete the groups success'), 204);
    }
    /**
     * Get list of companies by company ID.
     * @param int $companyId
     * @return JsonResponse
     */
    public function listByCompany(int $companyId): JsonResponse
    {
        return $this->handleResponse(FetchGroupsByCompany::run($companyId), __('Fetch groups by company'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Companies\BulkDeleteCompanies;
use App\Actions\Companies\CompaniesWithGroupsCount;
use App\Actions\Companies\CreateCompany;
use App\Actions\Companies\FetchCompanies;
use App\Actions\Companies\FetchCompany;
use App\Actions\Companies\UpdateCompany;
use App\Actions\Groups\FetchGroupsByCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteCompaniesRequest;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class GroupsAndCompaniesController extends Controller
{
    /**
     * Get all companies
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->handleResponse(FetchCompanies::run(), __('Fetched all companies'));
    }

    /**
     * Create new company.
     *
     * @param StoreCompanyRequest $request
     * @return JsonResponse
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = CreateCompany::run($request->validated());
        return $this->handleResponse($company, __('Created the company'));
    }

    /**
     * Get specific company by ID.
     * @param int $companyId
     * @return JsonResponse
     */
    public function show(int $companyId): JsonResponse
    {
        return $this->handleResponse(FetchCompany::run($companyId), __('Fetch the company'));
    }

    /**
     * @param UpdateCompanyRequest $request
     * @param Group $company
     * @return JsonResponse
     */
    public function update(UpdateCompanyRequest $request, Group $company): JsonResponse
    {
        $updated = UpdateCompany::run($company, $request->validated());
        return $this->handleResponse($updated, __('Updated the company'));
    }

    /**
     * Bulk delete companies with given IDs.
     *
     * @param BulkDeleteCompaniesRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteCompaniesRequest $request): JsonResponse
    {
        BulkDeleteCompanies::run($request->ids);
        return $this->handleResponse(null, __('Bulk delete the companies success'), 204);
    }

    /**
     * The Companies with groups count
     * @return JsonResponse
     */
    public function companiesWithGroupsCount(): JsonResponse
    {
        return $this->handleResponse(CompaniesWithGroupsCount::run(), __('Fetch companies with groups count'));
    }
}

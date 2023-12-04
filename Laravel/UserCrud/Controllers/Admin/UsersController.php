<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\BulkDeleteUsers;
use App\Actions\Users\CreateUser;
use App\Actions\Users\FetchUser;
use App\Actions\Users\FetchUsers;
use App\Actions\Users\UpdateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteUsersRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UsersController extends Controller
{
    /**
     * Get all users
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->handleResponse(FetchUsers::run(), __('Fetched all users'));
    }

    /**
     * Create new user.
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $user = CreateUser::run($validatedData);
        $user->assignRole('user');

        return $this->handleResponse($user, __('Created the user'));
    }

    /**
     * Update a user.
     *
     * @param StoreUserRequest $request The request object containing the validated data.
     * @param User $user The user object to be updated.
     * @return JsonResponse The JSON response indicating the result of the update.
     */
    public function update(StoreUserRequest $request, User $user): JsonResponse
    {
        $validatedData = $request->validated();
        $updated = UpdateUser::run($user, $validatedData);

        return $this->handleResponse($updated, __('Updated the user'));
    }

    /**
     * Bulk delete users with given IDs.
     *
     * @param BulkDeleteUsersRequest $request The request object containing the IDs of the users to delete.
     * @return JsonResponse The JSON response indicating the success of the bulk delete operation.
     */
    public function bulkDelete(BulkDeleteUsersRequest $request): JsonResponse
    {
        BulkDeleteUsers::run($request->ids);
        return $this->handleResponse(null, __('Bulk delete the users success'), 204);
    }

    /**
     * Get specific user by ID.
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        return $this->handleResponse(FetchUser::run($userId), __('Fetch the user'));
    }
}

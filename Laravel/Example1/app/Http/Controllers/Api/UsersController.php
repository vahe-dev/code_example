<?php

namespace App\Http\Controllers\Api;

use App\Actions\Church\FetchChurchUsersWithRoles;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Actions\Users\CreateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\UpdateUser;
use App\Actions\Users\FetchUsers;
use App\Http\Requests\Users\IndexUsersRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;

class UsersController extends BaseApiController
{
    /**
     * @param IndexUsersRequest $request
     * @return JsonResponse
     */
    public function index(IndexUsersRequest $request): JsonResponse
    {
        $loggedUser = $request->user();
        $filters = [];
        if (! $loggedUser->isSuperAdmin()) {
            $abilityChurchIds = $request->church_ids ?? [];
            $users = FetchChurchUsersWithRoles::run($abilityChurchIds, false);
            $filters['user_ids'] = $users->map(fn($u) => $u->user_id)->toArray();
        }
        return $this->handleResponse(
            FetchUsers::run(min($request->limit ?? self::DEFAULT_LIMIT, self::MAX_LIMIT), $filters),
            __('users.got_users'),
        );
    }

    /**
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = CreateUser::run($request->validated());
        return $this->handleResponse($user, __('users.added_the_user'));
    }

    /**
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        return $this->handleResponse(UpdateUser::run($request->validated(), $user->id), __('users.updated_the_user'));
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        return $this->handleResponse($user, __('users.shown_the_user'));
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        if (! DeleteUser::run($user)) {
            return $this->handleError(__('validation.errors.invalid_data'), ['error' => __('validation.errors.cannot_delete_item')]);
        }
        return $this->handleResponse(null, __('users.deleted_the_user'), 204);
    }
}

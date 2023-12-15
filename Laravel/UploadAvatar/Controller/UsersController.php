<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsersUploadAvatarRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBonus;
use App\Models\UserDocument;
use App\Traits\File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class UsersController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * Uploads a user's avatar.
     *
     * @param UsersUploadAvatarRequest $request - The request object containing the user's avatar and ID.
     * @return JsonResponse - The JSON response with the status, message, and data.
     */
    public function uploadAvatar(UsersUploadAvatarRequest $request)
    {
        $loggedUser = $request->user();
        $avatar = $request->file('avatar');
        $userId = $request->input('id');
        $user = User::find($userId);
        $isOwnerPage = (int)$loggedUser->id === (int)$userId;

        if ($loggedUser->can('upload avatar') || ($isOwnerPage && $loggedUser->can('upload own avatar'))) {
            if (Storage::exists('public/images/avatars/' . $user->id)) {
                Storage::deleteDirectory('public/images/avatars/' . $user->id);
            }

            $this->setDirectory('public/images/avatars/' . $user->id); // set directory for uploading avatar
            $uploadAvatarResult = $this->upload($avatar);
            if ($uploadAvatarResult['success']) {
                $urlImage = '/' . $user->id . $uploadAvatarResult['name'];
                $fillData['avatar'] = $urlImage;
                $updateAvatar = User::where('id', $user->id)->update($fillData);

                if ($updateAvatar) {
                    return response()->json(['status' => true, 'message' => __('New avatar for this user uploaded successfully!'), 'data' => $isOwnerPage ? $urlImage : null]);
                } else {
                    return response()->json(['status' => false, 'message' => __('Sorry, we were not able to upload a new photo, please try again!'), 'data' => null]);
                }
            }

            return response()->json(['status' => false, 'message' => __('Sorry, we were not able to upload a new photo, please try again!'), 'data' => null]);
        } else {
            return response()->json(['status' => false, 'message' => __('You do not have sufficient permissions to upload the image!'), 'data' => null]);
        }
    }
}
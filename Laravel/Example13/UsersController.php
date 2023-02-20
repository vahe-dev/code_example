<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\MailSwitch;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\Mail;
use App\Models\Country;
use App\Models\User;
use App\Models\UserDetail;

class UsersController extends BaseController
{
    public function updateAdminUsers(Request $request, $id){
        $data = $request->all();
        $page = $data['page'];

        if(!isset($data['role'])){
            $validator = Validator::make($data, [
                'name' => 'required|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required',
                'country' => 'required',
            ]);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $user = User::with(['details'])->find($id);
            $country = Country::where("name", $data['country'])->first();
            if(is_null($user->details) || !isset($user->details)){

                $new_user_details = new UserDetail;
                $new_user_details->user_id = $id;
                $new_user_details->country_id = $country->id;
                $new_user_details->region_id = 0;
                $new_user_details->media_id = 0;
                $new_user_details->city_id = 0;
                if($data['note'] == ""){
                    $new_user_details->note = null;
                }else{
                    $new_user_details->note = $data['note'];
                }
                $new_user_details->sex = 0;
                $new_user_details->phone = $data['phone'];

                $new_user_details->save();
                $user->save();
            }else{
                if($data['note'] == ""){
                    $user->details->note = null;
                }else{
                    $user->details->note = $data['note'];
                }
                if(!empty($country)){
                    $user->details->country_id = $country->id;
                }
                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->details->phone = $data['phone'];
                $user->save();
                $user->details->save();
            }


            return redirect('/admin/users?page='.$page.'#users');
        }else{

            $validator = Validator::make($data, [
                'name' => 'required|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required',
                'country' => 'required',
                'role' => 'required',
            ]);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $user = User::with(['details'])->find($id);
            if(isset($data['active'])){
                $user->active = 1;
            }
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->role = $data['role'];
            $country = Country::where("name", $data['country'])->first();

            if(is_null($user->details) || !isset($user->details)){
                $new_user_details = new UserDetail;
                $new_user_details->user_id = $id;
                $new_user_details->country_id = $country->id;
                $new_user_details->region_id = 0;
                $new_user_details->media_id = 0;
                $new_user_details->city_id = 0;
                $new_user_details->sex = 0;
                $new_user_details->phone = $data['phone'];
                $new_user_details->save();

                $user->save();
            }else{
                if(isset($data['active'])){
                    $user->status = 1;
                } else {
                    $user->status = 0;
                }
                if(!empty($country)){
                    $user->details->country_id = $country->id;
                }
                $user->details->phone = $data['phone'];
                $user->save();
                $user->details->save();
            }

            return redirect('/admin/users#adminusers');
        }
    }
}
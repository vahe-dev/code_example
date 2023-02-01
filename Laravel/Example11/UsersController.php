<?php

namespace App\Http\Controllers;

use App\Helpers\MailSwitch;
use App\Models\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersController extends Controller
{
    public function addMeeting(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:191',
            'email' => 'required|email|max:191',
            'phone' => 'required|max:191',
            'bdate' => 'required|max:191',
            'bplace' => 'required|max:191',
            'message' => 'required',
            'agree' => 'required'
        ]);

        $phone = str_replace('+', '', request('phone'));
        $phone = str_replace('-', '', $phone);
        $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        $firstElement = substr($phone, 0,1);

        if($firstElement == '7') {
            $phoneNumber = '+' . $phone;
        }else {
            $phoneNumber = $phone;
        }

        $date = Carbon::parse(request('bdate'))->format('Y-m-d');
        $meeting = new Meeting;
        $meeting->name = request('name');
        $meeting->email = request('email');
        $meeting->phone = $phoneNumber;
        $meeting->date = $date;
        $meeting->burnPlace = request('bplace');
        $meeting->message = request('message');
        $meeting->save();
        $email = request('email');

        $userInfo = new UserInfo();
        $userInfo->saveInfo($email, $meeting->name, $meeting->phone);
        $mail_data = [
            'name' => request('name'),
            'email' => request('email'),
            'phone' => $phoneNumber,
            'bdate' => request('bdate'),
            'bplace' => request('bplace'),
            'messsage' => request('message'),
        ];
        MailSwitch::send('admin','mails.meetings', $mail_data, function($message) use ($email){
            $message->to('email@email.email', 'Title')->subject('Subject');
            $message->from('email@email.email', 'Title');
        });

        return redirect()->back()->with('success', 'Ok.');
    }
}

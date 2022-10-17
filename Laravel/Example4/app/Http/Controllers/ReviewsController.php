<?php

namespace App\Http\Controllers;
use App\Helpers\MailSwitch;
use App\Models\Message;
use App\Models\ReviewCategory;
use App\Models\Review;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class ReviewsController extends Controller
{
    public function sentQuestion()
    {
        $this->validate(request(), [
            'name' => 'required|max:199|min:1',
            'email' => 'required|email|max:199',
            'phone' => 'required|max:199',
            'message' => 'required|min:3',
            'agree' => 'required',
            'captcha' => 'required'
        ]);

        if(request('captcha-value') && request('captcha-value') == request('captcha')) {

            $phone = str_replace('+', '', request('phone'));
            $phone = str_replace('-', '', $phone);
            $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
            $firstElement = substr($phone, 0,1);

            if($firstElement == '7') {
                $phoneNumber = '+' . $phone;
            }else {
                $phoneNumber = $phone;
            }

            $name = request('name');

            $from = config('mail.accounts.messenger.username');
            $mes = new Message();
            $mes->name = request('name');
            $mes->from = request('email');
            $mes->to =  $from;
            $mes->body = request('message');
            $mes->phone = $phoneNumber;
            $mes->viewed = 0;
            if(!$mes->save()){
                return redirect()->back()->with('error', 'Error!');
            }
            $mail_type = 'messenger';

            $mail_data = [
                'id' => $mes->id,
                'name' => request('name'),
                'email' => request('email'),
                'phone' => $phoneNumber,
                'text' => request('message')
            ];

            $mail = MailSwitch::send($mail_type, 'mails.question', $mail_data, function($message) use ($name, $from){
                $message->to($from, $name)->subject("Question from $name");
                $message->from($from, "'Your title'");
            });

            if (Mail::failures()) {
                return redirect()->back()->with('error', 'Try send the question again!.');
            }
            return redirect()->back()->with('success', 'Thanks for your question.');

        } else {
            return redirect()->back()->with('error', 'Please try again!.');
        }
    }
}

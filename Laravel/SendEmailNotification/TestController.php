<?php

namespace App\Http\Controllers;

use App\Mail\SendEmailNotification;
use Illuminate\Support\Facades\Mail;

class TestController extends Controller
{
    public function sendEmail()
    {
        $subject = 'test subject';
        $body = 'test body';
        Mail::to(config('mail.mailers.sendToEmails'))->send(new SendEmailNotification($subject, $body));

        return 'Email sent successfully!';
    }
}
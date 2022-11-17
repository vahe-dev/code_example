<?php

namespace App\Http\Controllers;

use App\Helpers\MailSwitch;
use App\Models\MasterClassClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\CartProduct;
use App\Models\ShopProduct;
use App\Models\Order;
use App\Models\ReservedTime;
use DB;
use Carbon\Carbon;
use App\Models\VideoConsultationForm;
use App\Models\Calendar;

class UsersController extends Controller
{
    public function continueConsultationProcess()
    {
        $this->validate(request(), [
            'customerData' => 'required',
            'improvedDate' => 'required',
            'improvedHour' => 'required',
            'dataID' => 'required',
            'productID' => 'required',
        ]);

        $reservedTime = new ReservedTime();
        $customerData = $reservedTime->makeCustomerData(request('customerData'));

        $userID = (Auth::user()) ? Auth::user()->id : $_COOKIE['guest_id'];

        \Log::info('---SKYPE consultation reserve date--- UserID=' . $userID . '---');
        \Log::info('productID=' . request('productID') . ', improvedDate=' . request('improvedDate') . ', improvedHour=' . request('improvedHour'));

        $creditExist = false;

        if(\Cookie::has('consultKey')) {
            $clientKey = \Cookie::get('consultKey');
            $clientOrder = Order::where('credit_key', $clientKey)->first();
            $clientOrderID = isset($clientOrder) ? $clientOrder->id : '';
            $clientOrderNumber = isset($clientOrder) ? $clientOrder->delivery_number : '';
            $clientEmail = isset($clientOrder) ? json_decode($clientOrder->payer_info)->email : '';
            $creditExist = isset($clientOrder) ? true : false;
        }

        $isOneHour = VideoConsultationForm::find(request('formID'));

        if ($isOneHour->one_hour) {
            $resDate = Carbon::parse(request('improvedDate'))->format('Y-m-d');
            $resHour2 = Carbon::parse(request('improvedHour'))->addMinutes(20)->format('H:i');
            $resHour3 = Carbon::parse(request('improvedHour'))->addMinutes(40)->format('H:i');

            $hour_exists = Calendar::where('improved_date', $resDate)
                ->whereIn('improved_hour', [request('improvedHour'), $resHour2, $resHour3])
                ->count();

            $checkDate = Calendar::where('improved_date', $resDate)
                ->whereIn('improved_hour', [request('improvedHour'), $resHour2, $resHour3])
                ->where('reserved_id', '!=', 0)
                ->whereNotNull('reserved_id')
                ->count();
        }else{
            $resDate = Carbon::parse(request('improvedDate'))->format('Y-m-d');
            $checkDate = Calendar::where('improved_date', $resDate)
                ->where('improved_hour', request('improvedHour'))
                ->where('reserved_id', '!=', 0)
                ->whereNotNull('reserved_id')
                ->count();

            $hour_exists = Calendar::where('improved_date', $resDate)
                ->where('improved_hour', request('improvedHour'))
                ->count();
        }

        if($checkDate > 0 || $hour_exists == 0) {
            $now = Carbon::now()->format('Y/m/d');
            $openHoures = Calendar::where('improved_date', '>=', $now)
                ->orderBy('improved_date', 'asc')
                ->distinct()
                ->get(['improved_date']);
            $oldestDate = Calendar::where('improved_date', '>', $now)->orderBy('improved_date', 'asc')->first(['improved_date']);
            $oldestDate = $oldestDate ? Carbon::parse($oldestDate->improved_date)->format('d.m.Y') : $oldestDate;

            $openHouresDates =[];
            foreach ($openHoures as $key => $value) {
                $openHouresDates[] = str_replace('-', '/', $openHoures[$key]['improved_date']);
            }

            $data=[
                'openHouresDates' => $openHouresDates,
                'oldestDate' => $oldestDate,
                'today' => $now
            ];

            return response()->json($data);
        }

        $pro = ShopProduct::with(['importProduct','sale'])->find(request('productID'));
        $isOneHour = VideoConsultationForm::find(request('formID'));
        $cartProduct = new CartProduct;
        $cartProduct->user_id = (Auth::user()) ? Auth::user()->id : '';
        $cartProduct->guest_id = (Auth::user()) ? '' : $_COOKIE['guest_id'];
        $cartProduct->product_id = $pro->id;
        $cartProduct->count = 1;
        $cartProduct->prime_cost = $pro->prime_cost;

        $salecount = count($pro['sale']);

        if($salecount && $pro['sale'][$salecount-1]['is_active'] == 1) {
            $cartProduct->price = $pro['sale'][$salecount-1]['price'];
            $cartProduct->price_total = $pro['sale'][$salecount-1]['price'];
        } else {
            $cartProduct->price = $pro->price;
            $cartProduct->price_total = $pro->price;
        }

        if(!$creditExist) {
            $cartProduct->save();
        }

        try {
            $addResTime = new ReservedTime;
            $addResTime->user_data = $customerData['userdata'];
            $addResTime->form_id = request('formID');

            if($creditExist) {
                $addResTime->order_id = $clientOrderID;
                $addResTime->payed = 1;
                $addResTime->canceled = 0;
            }

            $addResTime->improved_date = Carbon::parse(request('improvedDate'))->format('Y-m-d');
            $addResTime->improved_hour = request('improvedHour');

            if($creditExist) {
                $addResTime->user_id = $clientOrder->user_id ? $clientOrder->user_id : $clientOrder->guest_id;
            } else {
                $addResTime->user_id = (Auth::user()) ? Auth::user()->id : $_COOKIE['guest_id'];
            }

            $addResTime->user_message = $customerData['customerMessage'];
            $addResTime->skype_login = $customerData['skypeLogin'];
            $addResTime->video_token = md5(Carbon::now());
            $addResTime->is_one = $isOneHour->one_hour;
            $addResTime->cart_id = $cartProduct->id;

            if($addResTime->save()) {
                \Log::info('Saved user data to reserved_time');
            }
        } catch (\Exception $exception) {
            \Log::info('Error with save to reserved_time->' . $exception->getMessage());
        }

        $reserved_id = $addResTime->id;

        if ($isOneHour->one_hour) {

            $resDate = Carbon::parse(request('improvedDate'))->format('Y-m-d');
            $resHour2 = Carbon::parse(request('improvedHour'))->addMinutes(20)->format('H:i');
            $resHour3 = Carbon::parse(request('improvedHour'))->addMinutes(40)->format('H:i');

            Calendar::where('improved_date', $resDate)
                ->whereIn('improved_hour', [request('improvedHour'), $resHour2, $resHour3])
                ->update(['reserved_id' => $reserved_id]);
        }else{
            Calendar::where('id', request('dataID'))
                ->update(['reserved_id' => $reserved_id]);
        }

        if($creditExist) {

            $dates = ['Mon' => 'в понедельник', 'Tue' => 'во вторник', 'Wed' => 'в среду', 'Thu' => 'в четверг', 'Fri' => 'в пятницу', 'Sat' => 'в субботу', 'Sun' => 'в воскресенье'];
            $weekDay = $dates[date("D", strtotime(Carbon::parse(request('improvedDate'))->format('Y-m-d')))];
            $improvedDate = Carbon::parse(request('improvedDate'))->format('Y-m-d');
            $improvedHour = request('improvedHour');

            $mail_data = [
                'user' => $clientOrder->user_id ? true : false,
                'improved_date' => $improvedDate,
                'improved_hour' => $improvedHour,
                'weekDay' => $weekDay,
                'order_id' => $clientOrderNumber
            ];

            MailSwitch::send('consultation','mails.consulting', $mail_data, function($message) use ($clientEmail){
                $message->to($clientEmail, $clientEmail)->subject('Your Subject');
                $message->from(config('mail.accounts.consultation.username'), 'Title');
            });
        }

        if (Auth::user()) {
            $isInBlack = User::with(['details'])
                ->where('email', Auth::user()->email)
                ->whereHas('details', function ($query)
                {$query->where('blacklist', 1);})
                ->count();

            if ($isInBlack) {
                $mail_data = [
                    'user_name' => Auth::user()->name,
                    'user_email' => Auth::user()->email,
                    'reserve_id' => $addResTime->id,
                ];

                MailSwitch::send('admin','mails.blacklistUser', $mail_data, function($message){
                    $message->to(config('mail.accounts.admin.username'), 'Title')->subject('Your Subject');
                    $message->from(config('mail.accounts.info.username'), 'Title');
                });
            }

            if($creditExist) {
                echo json_encode(['creditExist' => $creditExist, 'improvedDate' => $improvedDate, 'improvedHour' => $improvedHour, 'weekDay' => $weekDay ]);
            } else {
                echo json_encode('OK');
            }
        }else{
            if($creditExist) {
                echo json_encode(['creditExist' => $creditExist, 'improvedDate' => $improvedDate, 'improvedHour' => $improvedHour, 'weekDay' => $weekDay ]);
            } else {
                echo json_encode('X');
            }
        }
    }
}
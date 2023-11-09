<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\ReservedTime;
use App\Models\Order;
use App\Helpers\MailSwitch;

class OrderController extends Controller
{
    /**
     * Process webhook request from Tinkoff for credit payment
     *
     * @param Request $request The request object containing the webhook data
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     * */
    public function responseFromTinkoff(Request $request)
    {
        \Log::info('----Tinkoff Response---');
        \Log::info(['request' => $request->all()]);

        if ($request->input('id')) {
            $clientOrder = Order::where('delivery_number', $request->input('id'))->first();

            if(isset($clientOrder)) {

                $orderID = $clientOrder->id;
                $clientEmail = $clientOrder->payer_info ? json_decode($clientOrder->payer_info)->email : '';

                if($clientEmail) {

                    $totalPrice = $clientOrder->price_total;
                    $creditPrice = $request->input('credit_amount');
                    $payStatusTinkoff = $request->input('status') ? $request->input('status') : '';
                    $clientName = $request->input('first_name') ? $request->input('first_name') : '';
                    $clientLastName = $request->input('last_name') ? $request->input('last_name') : '';
                    $clientPhone = $request->input('phone') ? $request->input('phone') : '';

                    if($payStatusTinkoff == "Signed" || $payStatusTinkoff == "signed") {
                        $paymentStatus = 3;
                    } elseif ($payStatusTinkoff == "Approved" || $payStatusTinkoff == "approved") {
                        $paymentStatus = 4;
                    } elseif ($payStatusTinkoff == "Rejected" || $payStatusTinkoff == "rejected") {
                        $paymentStatus = 5;
                    } elseif ($payStatusTinkoff == "Canceled" || $payStatusTinkoff == "canceled") {
                        $paymentStatus = 6;
                    } else {
                        $paymentStatus = 2;
                    }

                    if($clientOrder->credit_key) {

                        $payer_info = json_encode([
                            'weight' => 0,
                            'name' => $clientName . ' ' . $clientLastName,
                            'email' => $clientEmail,
                            'phone' => $clientPhone,
                            'address' => null,
                            'zip' => null,
                            'deliveryCode' => null,
                            'deliveryAddress' => "",
                            'deliveryPrice' => null
                        ]);

                        Order::where('id', $orderID)->update([
                            'payer_info' => $payer_info,
                            'payment_status' => $paymentStatus,
                        ]);

                        if($paymentStatus == 3) {

                            $clientURL = request()->getSchemeAndHttpHost() . '/skype-consultation/' . $clientOrder->credit_key;

                            $mail_data = [
                                'orderID' => $request->input('id'),
                                'clientURL' => $clientURL
                            ];

                            try {
                                MailSwitch::send('consultation','mails.creditConsultation', $mail_data, function($message) use ($clientEmail){
                                    $message->to($clientEmail, $clientEmail)->subject('Your subject');
                                    $message->from(config('mail.accounts.consultation.username'), 'Consultation');
                                });

                                \Log::info('OrderID-' . $orderID . ' -> Credit confirmed, Email sent to client, PaymentStatus -> ' . $request->input('status'));

                            } catch (\Exception $exception) {
                                \Log::info('OrderID-' . $orderID . ', Email Not Sent, Error -> ' .  $exception->getMessage());
                            }
                        }

                    } else {

                        if($paymentStatus == 3) {

                            \Log::info('OrderID-' . $orderID . ' -> Credit confirmed, PaymentStatus -> ' . $request->input('status'));
                            $res = ReservedTime::where('order_id',$orderID)->first();
                            if(count($res) > 0) {
                                ReservedTime::where('order_id',$orderID)->update(['payed'=>1, 'canceled' => 0]);
                            }

                            return redirect('/payment/robokassa/success?inv_id=' . $request->input('id') . '&creditExist=' . true);

                        } else {
                            Order::where('id', $orderID)->update([
                                'payment_status' => $paymentStatus
                            ]);
                        }
                    }

                }  else {
                    \Log::info('OrderID-' . $orderID . ' -> ClientEmail not exist in database');
                    exit;
                }

            } else {
                \Log::info('OrderID-' . $request->input('id') . ' -> OrderID from Tinkoff not exist in database');
                exit;
            }

        } else {
            \Log::info('OrderID not exist in json from Tinkoff');
            exit;
        }
    }
}
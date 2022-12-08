<?php

namespace App\Http\Controllers;

use App\Helpers\GetB2cpl;
use App\Models\CartProduct;
use App\Models\Order;
use App\Models\UserInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function order(Request $request)
    {
        $this->validate($request, [
            'address' => isset($request->onlyVirtual) ? '' : 'required',
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|regex:/^[0-9\-\(\)\/\+\s]*$/',
            'agree' => 'required'
        ]);

        $preOrder = 0;
        $phoneNumber = request('phone');

        $userInfo = new UserInfo();
        $userInfo->saveInfo($request->email, $request->name.' '.$request->surname , $phoneNumber);

        $currentId = !Auth::user() ? $_COOKIE['guest_id'] :  Auth::user()->id;
        $current_id = !Auth::user() ? 'guest_id' :  'user_id';

        $cartAllProducts = CartProduct::with(['product'])
            ->where($current_id, $currentId)
            ->get();

        $totalPrice = $totalWeight = 0;

        if(count($cartAllProducts)){
            $allIdSForPromo = [];
            $cartIDForReserveTimeOrder = [];

            foreach ($cartAllProducts as $total) {

                if ($total->product->group->preorder == 1) {
                    $preOrder = 1;
                }

                if($total['product']['active'] == 0) {
                    echo json_encode(['error' => 'Tовар "'.$total['product']['group']['product_name'].'" отсуствует на складе.']);
                    return;
                }

                $totalPrice += $total['price_total'];
                $totalWeight += $total['count']*$total['product']['weight'];
                $allIdSForPromo[] = $total['product']['id'];
                if ($total['product']['category']['id'] == 1) {
                    $cartIDForReserveTimeOrder[] = $total['id'];
                }
            }

            if(Auth::user() && !Order::where('user_id', auth()->id())->where('payment_status', 1)->count()){
                $pre100 = Gift::where('type', 'discount')->first();
                $totalPrice = $pre100 ? (int) $totalPrice - (int) $pre100->price : $totalPrice;
            }

            $b2cpl = new GetB2cpl();
            $dalPrice = 0;
            if(isset($request->index) && !empty($request->index) && isset($request->deliveryDataType)) {
                $del_ways = $b2cpl->getDeliveryZipData(request('index'), $totalWeight);
                $del_ways = json_decode($del_ways, true);
                $del_ways = $del_ways[$request->deliveryDataType];
                $del_ways = isset($request->transKey) ? $del_ways[request('transKey')] : 0;
                $dalPrice = $del_ways ? $del_ways['price'] : 0;
            }

            $outSummm = (int)$dalPrice+$totalPrice.'.00';
            $dal_price = $dalPrice;
            $promo_id = 0;
            $discount_group = 0;
            $myJson = '';

            $order = new Order;
            $order->user_id = (Auth::user()) ? Auth::user()->id : null;
            $order->guest_id = (isset($_COOKIE['guest_id'])) ? $_COOKIE['guest_id'] : null;
            $order->price_total = $totalPrice;
            $order->promo_id = $promo_id;
            $order->discount_group = $discount_group;
            $microtime = explode('.',microtime(true));
            $l = 9 - strlen($microtime[1]);
            $delivery_number = substr($microtime[0],0, $l).$microtime[1];
            $order->payer_info = json_encode([
                'weight' => $totalWeight,
                'name' => request('name').' '.request('surname'),
                'email' => request('email'),
                'phone' => $phoneNumber,
                'address' => request('address'),
                'zip' => request('index'),
                'deliveryCode' => request('transferCode'),
                'deliveryAddress' => request('transferAddress'),
                'deliveryPrice' => $dal_price
            ]);
            $order->delivery_price = $dal_price;
            $order->delivery_number = $delivery_number;
            $order->country_code = request('countryId');
            $order->preorder = $preOrder;
            $order->save();
            $delivery_number = substr($order->delivery_number, 0, -strlen($order->id));
            $delivery_number .= $order->id;
            $order->delivery_number = $delivery_number;
            $order->save();

            $outSum = $outSummm;

            if(request('paymentType') == 'robokassa') {

                $jsonDataItems = [];
                foreach ($cartAllProducts as $product) {
                    $jsonDataItems [] =
                        (object)[
                            "name" => $product->product->category->title,
                            "quantity" => $product->count,
                            "sum" => $product->price_total,
                            "payment_method" => "full_payment",
                            "tax" => "none"
                        ];
                };

                if ($dal_price && $dal_price != 0) {
                    $jsonDataItems [] =
                        (object)[
                            "name" => "Доставка",
                            "quantity" => 1,
                            "sum" => $dal_price,
                            "payment_method" => "full_payment",
                            "tax" => "none"
                        ];
                }

                $jsonDataReceipt = [
                    "sno" => "usn_income_outcome",
                    "items" => $jsonDataItems
                ];

                $roboLogin = '*********';
                $pass1 = '*********';
                $roboPass = md5($roboLogin.':'. $outSum .':'. $delivery_number .':'. json_encode($jsonDataReceipt, JSON_UNESCAPED_UNICODE) .':'.$pass1);
                $myJson = [
                    'paymentType' => request('paymentType'),
                    'roboPass' => $roboPass,
                    'outSum' => $outSum,
                    'roboLogin' => $roboLogin,
                    'invoceId' => $delivery_number,
                    'description' => 'description...',
                    'receipt' => json_encode($jsonDataReceipt, JSON_UNESCAPED_UNICODE)
                ];
            }
            echo json_encode($myJson);
        }else {
            echo json_encode(['error' => 'В вашей корзине нет товаров.']);
        }
    }
}
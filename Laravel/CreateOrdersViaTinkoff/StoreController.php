<?php

namespace App\Http\Controllers;

use App\Helpers\MailSwitch;
use App\Models\UserInfo;
use http\Client\Request;
use App\Models\Banner;
use App\Models\ShopProductType;
use App\Models\ShopProductCategory;
use App\Models\ShopProductGroup;
use App\Models\Media;
use Illuminate\Support\Facades\Auth;
use App\Models\ShopProduct;
use App\Models\PriceGift;
use App\Models\Country;
use App\Models\Calendar;
use App\Models\Promo;
use DB;
use App\Models\ReservedTime;
use App\Models\Gift;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderFee;
use App\Models\UserDetail;
use App\Models\DeliveryRegion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    /**
     * Create Order via Tinkoff payment.
     *
     * @param Request $request
     * @throws Some_Exception_Class description of exception
     * @return void
     */
    public function orderTinkoff(Request $request)
    {
        if ($request->productID) {
            if ($request->clientEmail && filter_var($request->clientEmail, FILTER_VALIDATE_EMAIL)) {
                $shopProduct = ShopProduct::with(['importProduct','sale'])->find($request->productID);
                $saleCount = count($shopProduct['sale']);
                $sale = $saleCount && $shopProduct['sale'][$saleCount-1]['is_active'] == 1 ? $shopProduct['sale'][$saleCount-1] : null;
                $price = $sale ? $sale['price'] : $shopProduct->price;
                $price_total = $price;

                $microtime = explode('.', microtime(true));
                $l = 9 - strlen($microtime[1]);
                $delivery_number = StoreController . phpsubstr($microtime[0], 0, $l) . $microtime[1];

                $order = new Order;
                $order->user_id = optional(Auth::user())->id;
                $order->guest_id = $_COOKIE['guest_id'] ?? null;
                $order->price_total = $price_total;
                $order->payer_info = json_encode([
                    'weight' => 0,
                    'name' => "",
                    'email' => $request->clientEmail,
                    'phone' => "",
                    'address' => null,
                    'zip' => null,
                    'deliveryCode' => null,
                    'deliveryAddress' => "",
                    'deliveryPrice' => null
                ]);
                $order->payment_status = 2;
                $order->delivery_number = $delivery_number;
                $order->save();

                $delivery_number = StoreController . phpsubstr($order->delivery_number, 0, -strlen($order->id)) . $order->id;
                $order->delivery_number = $delivery_number;
                $order->credit_key = md5($order->id);
                $order->save();

                $orderProductData = [
                    'order_id' => $order->id,
                    'cart_id' => 0,
                    'product_id' => $request->productID,
                    'count' => 1,
                    'price' => $price,
                    'prime_cost' => $shopProduct->prime_cost,
                    'price_total' => $price_total,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                OrderProduct::insert([$orderProductData]);

                $responseTinkoff = $this->getTinkoffUrl($price_total, $delivery_number);

                $myJson = [
                    'id' => $responseTinkoff['id'],
                    'tinkoffUrl' => $responseTinkoff['link'],
                ];

                Log::info('----Tinkoff OrderID--- ' . $order->id . ', ' . $delivery_number . ', ' . $responseTinkoff['id']);

                echo json_encode($myJson);
            } else {
                echo json_encode(['error2' => 'error']);
            }
        } else {
            echo json_encode(['error1' => 'error']);
        }
    }

    /**
     * Generates the Tinkoff payment URL for a given price and order number.
     *
     * @param int $price The price of the order.
     * @param string $order_number The order number.
     * @return array The decoded JSON response from the Tinkoff API.
     */
    private function getTinkoffUrl($price, $order_number)
    {
        $shopId = '***********';
        $showcaseId = '**********';
        $apiUrl = 'https://forma.tinkoff.ru/api/partners/v2/orders/create';

        $jsonData = [
            "shopId" => $shopId,
            "showcaseId" => $showcaseId,
            "sum" => $price,
            "orderNumber" => $order_number,
            "items" => [
                (object)[
                    "name" => "name",
                    "quantity" => 1,
                    "price" => $price
                ],
            ]
        ];

        $curl = curl_init($apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($jsonData, JSON_UNESCAPED_UNICODE));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

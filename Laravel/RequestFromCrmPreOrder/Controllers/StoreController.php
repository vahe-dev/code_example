<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    /**
     * Requests pre-orders from the CRM system.
     *
     * @param Request $request The HTTP request object.
     * @return string The result of the request.
     */
    public function requestFromCrmPreOrder(Request $request)
    {
        \Log::info('-------Request for preOrders from CRM system--------');
        if($request->id) {

            \Log::info('OrderID - ' . $request->id);
            $orderId = (int) preg_replace('/\D/', '', $request->id);
            $orderInfo = Order::where('id',$orderId)->first();

            if ($orderInfo) {
                $microTime = explode('.',microtime(true));
                $l = 9 - strlen($microTime[1]);
                $delivery_number = substr($microTime[0],0, $l).$microTime[1];
                $delivery_number = substr($delivery_number, 0, -strlen($orderId));
                $delivery_number .= $orderId;

                $orderItems = OrderProduct::with('product')->where('order_id', $orderId)->get()->toArray();

                $preOrder = 0;

                foreach ($orderItems as $item) {
                    if ($item['product']['group']['preorder'] == 1) {
                        $preOrder = 1;
                    }
                }

                Order::where('id', $orderId)->update([
                    'preorder' => $preOrder,
                    'delivery_number' => $delivery_number
                ]);

                return 'success';
            } else {
                Log::info('OrderID not exists in Database');
            }
        } else {
            Log::info('OrderID not exists in request');
        }
    }
}

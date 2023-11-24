<?php

namespace app\modules\main\controllers;

use common\models\Orders;
use common\models\ShopifyOrders;
use common\models\ShopifyOrdersRu;
use Yii;
use frontend\controllers\Controller;
use yii\db\Query;

class OrdersController extends Controller
{
    public function actionShopifyGetOrdersRu()
    {
        if (Yii::$app->request->get('token') == '************') {

            try {

                $shop = "shop name";
                $api_key = "***********";
                $api_pass = "**********";
                $ordersIdsArray = [];

                $ordersIDs = ShopifyOrdersRu::find()->select('order_id')->all();

                if($ordersIDs){
                    foreach($ordersIDs as $id){
                        $ordersIdsArray[] = $id->order_id;
                    }
                }

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://".$api_key . ":". $api_pass ."@". $shop .".myshopify.com/admin/api/2023-10/orders.json",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_POSTFIELDS => "",
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json",
                        "cache-control: no-cache"
                    ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo "cURL Error #:" . $err;
                }

                $shopifyOrders = json_decode($response)->orders;

                if($shopifyOrders) {

                    foreach ($shopifyOrders as $order) {

                        if (!in_array($order->name, $ordersIdsArray)) {

                            $orderDetails = [];

                            foreach ($order->line_items as $orderProducts) {
                                $orderProducts->price = str_replace('.',',',$orderProducts->price);
                                $order->total_price = str_replace('.',',',$order->total_price);
                                $orderDetails[] = $orderProducts->sku . ', ' . $orderProducts->price . '; ';
                            }

                            if($order->payment_gateway_names[0] == 'Cash on Delivery (COD)') {
                                $paymentMethod = 'Оплата наличными';
                            } elseif (strpos($order->note, "Быстрый заказ") !== false) {
                                $paymentMethod = 'oneclick';
                            } else {
                                $paymentMethod = $order->payment_gateway_names[0];
                            }

                            $orderDetails2 = implode('', $orderDetails);
                            $tempMail = "Order: <b>"  . $order->name . "</b><br>"
                                . "First Name: <b>" . ($order->customer->first_name ?: NULL) . "</b><br>"
                                . "Last Name: <b>" . ($order->customer->last_name ?: NULL) . "</b><br>"
                                . "Email: <b>" . ($order->email ?: NULL) . "</b><br>"
                                . "Phone: <b>" . ($order->customer->phone ?: NULL) . "</b><br>"
                                . "Address: <b>" . ($order->customer->default_address->address1 ?: NULL) . "</b><br>"
                                . "Country: <b>" . ($order->customer->default_address->country ?: NULL) . "</b><br>"
                                . "City: <b>" . ($order->customer->default_address->city ?: NULL) . "</b><br>"
                                . "Zip: <b>" . ($order->customer->default_address->zip ?: NULL) . "</b><br>"
                                . "Order details: <b>" . $orderDetails2 . "</b><br>"
                                . "Payment Method: <b>" . $paymentMethod . "</b><br>"
                                . "Amount: <b>" . $order->total_price . "</b><br>"
                                . "Currency: <b>" . $order->currency . "</b><br>";

                            $shopifyModel = new ShopifyOrdersRu();
                            $shopifyModel->order_id = $order->name;
                            $shopifyModel->email = $order->email;
                            $shopifyModel->orders_date = $order->updated_at;
                            $shopifyModel->created_at = date('Y-m-d H:i:s');
                            if ($shopifyModel->save()) {

                                    Yii::$app->mailer->compose()
                                        ->setFrom('from@email.email')
                                        ->setTo('test@test.test')
                                        ->setSubject('Subject')
                                        ->setHtmlBody($tempMail)
                                        ->send();

                                \Yii::info('Email sent', 'debug');
                                \Yii::info('OrderID - ' . $order->name . ',MailBody - ' . $tempMail . 'date to for crone: ', 'debug');
                            }
                        }
                    }
                }
            } catch (\Exception $exception) {
                \Yii::info('Email Not sent', 'debug');
                \Yii::info($exception->getMessage(), 'debug');
            }
        }
    }









    // добавление email подписчика












}


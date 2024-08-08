<?php

use models\ShopifyOrdersRetail;
use Yii;
use frontend\controllers\MyFrontendController;

class ZapierController extends MyFrontendController
{
    /**
     * Get recent orders from shopify(Retail) and send to zapier(google sheet).
     *
     */
    public function actionShopifyRetailOrders()
    {
        if (Yii::$app->request->get('token') == '***********') {

            try {
                $shopName = "******";
                $accessToken = "**********************";
                $queryParams = "status=any";

                $ordersIdsArray = ShopifyOrdersRetail::find()->select('order_id')->column();
                $shopifyOrders = $this->getShopifyOrdersNew($shopName, $accessToken, $queryParams);

                if($shopifyOrders) {

                    foreach ($shopifyOrders as $order) {
                        $orderID = str_replace('#', '', $order->name);

                        if (!in_array($orderID, $ordersIdsArray)) {

                            $orderDetails = [];

                            foreach ($order->line_items as $orderProducts) {
                                $orderDetails[] = $orderProducts->sku . ', ' . $orderProducts->price . '; ';
                            }
                            $orderItems = implode('', $orderDetails);

                            $tempMail = "Order: <b>"  . $orderID . "</b><br>"
                            . "First Name: <b>" . ($order->customer->first_name ?: 'xxxxxxx') . "</b><br>"
                            . "Last Name: <b>" . ($order->customer->last_name ?: 'xxxxxxx') . "</b><br>"
                            . "Email: <b>" . ($order->email ?: 'xxxxxxx') . "</b><br>"
                            . "Phone: <b>" . ($order->phone ?: 'xxxxxxx') . "</b><br>"
                            . "Address: <b>" . ($order->customer->default_address->address1 ?: 'xxxxxxx') . "</b><br>"
                            . "Country: <b>" . ($order->customer->default_address->country ?: 'xxxxxxx') . "</b><br>"
                            . "City: <b>" . ($order->customer->default_address->city ?: 'xxxxxxx') . "</b><br>"
                            . "Zip: <b>" . ($order->customer->default_address->zip ?: 'xxxxxxx') . "</b><br>"
                            . "Order details: <b>" . $orderItems . "</b><br>"
                            . "Payment Method: <b>" . ($order->payment_gateway_names[0]) . "</b><br>"
                            . "Amount: <b>" . $order->total_price . "</b><br>"
                            . "Currency: <b>" . $order->currency . "</b><br>";

                            $shopifyModel = new ShopifyOrdersRetail();
                            $shopifyModel->order_id = $orderID;
                            $shopifyModel->email = $order->email;
                            $shopifyModel->orders_date = $order->updated_at;
                            $shopifyModel->created_at = date('Y-m-d H:i:s');

                            if ($shopifyModel->save()) {

                                $mails = ['test@test.test', 'test@test.test1'];
                                foreach ($mails as $email) {
                                    Yii::$app->mailer->compose()
                                        ->setFrom('info@info.info')
                                        ->setTo($email)
                                        ->setSubject('Subject')
                                        ->setHtmlBody($tempMail)
                                        ->send();
                                }

                                \Yii::info('--------- Order successfully sent to zapier from Shopify Retail --------------', 'debug');
                                \Yii::info('OrderID - ' . $orderID . ',MailBody - ' . $tempMail . 'date to for crone: ', 'debug');
                            }
                        }
                    }
                }

            } catch (\Exception $exception) {
                \Yii::info('------------ Failed to send order to zapier from Shopify Retail -----------------', 'debug');
                \Yii::info($exception->getMessage(), 'debug');
            }
        }
    }

    private function getShopifyOrdersNew($shop, $accessToken, $queryParams)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://" . $shop . ".myshopify.com/admin/api/2024-07/orders.json?" . $queryParams,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'X-Shopify-Access-Token: ' . $accessToken,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response)->orders;
    }
}

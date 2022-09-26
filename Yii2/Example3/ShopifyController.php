<?php

namespace app\modules\main\controllers;

use yii;
use yii\db\Query;
use common\models\XmlHistory;


/**
 * Get new orders from shopify and to generate xml file with new orders
 */
class ShopifyController
{

    private $shop = "Your Shop";
    private $api_key = "Your APi key";
    private $api_pass = "Your API password";
    private $api_Url = ".myshopify.com/admin/api/2021-01/";

    public function actionGetOrders()
    {
        try {

            $filePath = '/Your/path/for/xml/file/';
            $OrdersShopify = $this->getOrdersShopify();
            $Orders = json_decode($OrdersShopify)->orders;

            $totalAmount = 0;
            $totalDiscount = 0;
            $selectOrdersIDs = [];
            $orderItems = XmlHistory::find()->select('order_id')->asArray()->all();

            foreach ($orderItems as $orderItem) {
                $selectOrdersIDs[] = $orderItem['order_id'];
            }

            if ($Orders) {

                foreach ($Orders as $order) {

                    if (!in_array($order->name, $selectOrdersIDs)) {

                        $fileCreated = false;
                        foreach ($order->shipping_lines as $shipping_line) {
                            $shipping = $shipping_line->price;
                        }

                        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';

                        $xmlData .= '<Order>';
                        $xmlData .= '<OrderID>' . $order->name . '</OrderID>';
                        $xmlData .= '<Date>' . date('d-m-Y H:i:s', strtotime($order->created_at)) . '</Date>';
                        $xmlData .= '<FirstName>' . ($order->customer->first_name ? $order->customer->first_name : null) . '</FirstName>';
                        $xmlData .= '<LastName>' . ($order->customer->last_name ? $order->customer->last_name : null) . '</LastName>';
                        $xmlData .= '<Email>' . ($order->email ? $order->email : null) . '</Email>';
                        $xmlData .= '<Phone>' . ($order->shipping_address->phone ? $order->shipping_address->phone : null) . '</Phone>';
                        $xmlData .= '<Country>' . ($order->customer->default_address->country ? $order->customer->default_address->country : null) . '</Country>';
                        $xmlData .= '<City>' . ($order->customer->default_address->city ? $order->customer->default_address->city : null) . '</City>';
                        $xmlData .= '<Address>' . ($order->customer->default_address->address1 ? $order->customer->default_address->address1 : null) . '</Address>';
                        $xmlData .= '<ZipCode>' . ($order->customer->default_address->zip ? $order->customer->default_address->zip : null) . '</ZipCode>';
                        $xmlData .= '<OrderDetalis>';

                        foreach ($order->line_items as $order_item) {

                            $discountPromoProduct = 0;

                            $variantsShopify = $this->getVariantsShopify($order_item->variant_id);
                            $variantsPrice = json_decode($variantsShopify)->variant->compare_at_price;

                            $productPrice = isset($variantsPrice) && $variantsPrice > 0 ? $variantsPrice : $order_item->price;

                            if($order_item->discount_allocations) {
                                foreach ($order_item->discount_allocations as $k) {
                                    $discountPromoProduct = $k->amount;
                                }
                            }

                            $discountProduct = ($productPrice - $order_item->price) * $order_item->quantity + $discountPromoProduct;
                            $totalDiscount += ($productPrice - $order_item->price) * $order_item->quantity;
                            $totalAmount += $variantsPrice && $variantsPrice > 0 ? $variantsPrice * $order_item->quantity : $order_item->price * $order_item->quantity;

                            $xmlData .= '<SKU>' . ($order_item->sku ? $order_item->sku : null) . '</SKU>';
                            $xmlData .= '<Quantity>' . ($order_item->quantity ? $order_item->quantity : null) . '</Quantity>';
                            $xmlData .= '<Price>' . ($productPrice ? $productPrice : null) . '</Price>';
                            $xmlData .= '<Discount>' . $discountProduct . '</Discount>';
                            $xmlData .= '<FinalPrice>' . ($order_item->quantity * $order_item->price - $discountPromoProduct) . '</FinalPrice>';
                        }

                        $xmlData .= '</OrderDetalis>';
                        $xmlData .= '<TotalAmount>' . ($totalAmount ? $totalAmount  : null) . '</TotalAmount>';
                        $xmlData .= '<TotalDiscount>' . ($order->total_discounts + $totalDiscount) . '</TotalDiscount>';
                        $xmlData .= '<Shipping>' . ($shipping ? $shipping : null) . '</Shipping>';
                        $xmlData .= '<FinalTotalAmount>' . ($order->total_price ? $order->total_price : null) . '</FinalTotalAmount>';
                        $xmlData .= '<PaymentMethod>' . ($order->payment_gateway_names[0] ? $order->payment_gateway_names[0] : null) . '</PaymentMethod>';
                        $xmlData .= '<PaymentStatus>' . ($order->financial_status ? $order->financial_status : null) . '</PaymentStatus>';
                        $xmlData .= '<Currency>' . ($order->currency ? $order->currency : null) . '</Currency>';

                        if($order->financial_status == 'paid') {

                            $infoRobkassa = $this->getPaymentInfo($order->id);
                            $infoTransaction = json_decode($infoRobkassa)->transactions;

                            $xmlData .= '<InfoPayment>';
                            $xmlData .= '<AuthorizationKey>' . ($infoTransaction[0]->authorization ? $infoTransaction[0]->authorization : null) . '</AuthorizationKey>';
                            $xmlData .= '<Amount>' . ($infoTransaction[0]->amount ? $infoTransaction[0]->amount : null) . '</Amount>';
                            $xmlData .= '<CreateDate>' . ($infoTransaction[0]->created_at ? date('d-m-Y H:i:s', strtotime($infoTransaction[0]->created_at)) : null) . '</CreateDate>';
                            $xmlData .= '</InfoPayment>';

                        } else {
                            $xmlData .= '<InfoPayment>';
                            $xmlData .= '<AuthorizationKey>' . ' ' . '</AuthorizationKey>';
                            $xmlData .= '<Amount>'. ' ' .'</Amount>';
                            $xmlData .= '<ProcessedDate>'. ' ' .'</ProcessedDate>';
                            $xmlData .= '</InfoPayment>';
                        }

                        $xmlData .= '</Order>';

                        $fileName = 'order_' . date('d-m-Y', strtotime($order->created_at)) . "_" . date("H-i-s", strtotime($order->created_at)) . '.xml';
                        $fileFullName = $filePath . $fileName;

                        if(file_put_contents($fileFullName,$xmlData)) {

                            $xml1cHistory = new XmlHistory();
                            $xml1cHistory->order_id = $order->name;
                            $xml1cHistory->xml = $xmlData;
                            $xml1cHistory->created_at = date('Y-m-d H:i:s', strtotime($order->created_at));
                            $xml1cHistory->save();

                            $fileCreated = true;
                        }

                        if($fileCreated == true) {
                            \Yii::info('------- XML file created successfully ------- '. $fileName  . ', OrderID = '. $order->name  , 'debug');
                        }

                        $this->deleteXmlHistory();
                    }
                }
            }

            } catch (\Exception $exception) {
                \Yii::info('------------ XML file failed  -----------------', 'debug');
                \Yii::info($exception->getMessage(), 'debug');
            }

    }

    private function getOrdersShopify()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://" . $this->api_key . ":" . $this->api_pass . "@" . $this->shop . $this->api_Url . "orders.json",
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

        return $response;
    }

    private function getVariantsShopify($variantId)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https:// " . $this->api_key . ":" . $this->api_pass . "@" . $this->shop . $this->api_Url . "variants/". $variantId . ".json",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
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

        return $response;
    }

    private function getPaymentInfo($orderID)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https:// " . $this->api_key . ":" . $this->api_pass . "@" . $this->shop . $this->api_Url . "orders/" . $orderID . "/transactions.json",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
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

        return $response;
    }

    private function deleteXmlHistory()
    {
        $date = date('Y-m-d');
        $deleteDate = date('Y-m-d', strtotime($date . ' -30 day' ));

        $selectIDs = XmlHistory::find()
            ->where(['DATE(updated_at)' => $deleteDate])
            ->asArray()
            ->all();

        foreach ($selectIDs as $id) {
            $deleteIDs [] = $id['id'];
        }

        \Yii::$app->db
            ->createCommand()
            ->delete('xml_history', ['id' => $deleteIDs])
            ->execute();
    }
}
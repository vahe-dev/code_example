<?php

namespace app\modules\main\controllers;

use frontend\controllers\MyFrontendController;
use yii;

/**
 *Controller for my target service
 */

class MytargetController extends MyFrontendController
{
    protected $shop = "Your shop";
    protected $api_key = "Your api key";
    protected $api_pass = "Your api password";
    protected $responseProducts = [];
    protected $categoryID = 1;
    protected $categories = [];

    /**
     * auto update XML file for my target service
     */

    public function actionGenerateXml()
    {
            try {
                $date = date("Y-m-d h:i");
                $filePath = 'your_file_path/trg.xml';

                 $this->getProducts();

                if($this->responseProducts) {

                    $categoryArr = [];

                    $xmlData = '<?xml version="1.0" encoding="utf-8"?>';

                    $xmlData .= '<yml_catalog date="' . $date . '">';
                    $xmlData .= '<shop>';
                    $xmlData .= '<name>' . 'Интернет-магазин' . '</name>';
                    $xmlData .= '<company>' . 'Your company name' . '</company>';
                    $xmlData .= '<url>' . 'web site url' . '</url>';
                    $xmlData .= '<currencies>';
                    $xmlData .= '<currency id="RUB" />';
                    $xmlData .= '</currencies>';
                    $xmlData .= '<categories>';
                    $xmlData .= '<category id="1">Category Name</category>';

                    foreach ($this->responseProducts as $products) {

                        foreach ($products as $item) {

                            if($item->published_at) {

                                if(!in_array($item->product_type, $this->categories)) {

                                    $xmlData .= '<category id="' . ++$this->categoryID . '" parentId="1" >' . $item->product_type . '</category>';

                                    $this->categories[] = $item->product_type;

                                    $categoryArr[$item->product_type] = $this->categoryID;
                                }
                            }
                        }
                    }

                    $xmlData .= '</categories>';
                    $xmlData .= '<offers>';

                    foreach ($this->responseProducts as $products) {

                        foreach ($products as $item) {

                            if($item->published_at) {

                                foreach ($item->variants as $variant) {
                                    $price = $variant->price;
                                    break;
                                }

                                $xmlData .= isset($item->id) ? '<offer id="' . $item->id . '" available="true">' : '';
                                $xmlData .= isset($item->handle) ? '<url>'  . 'web_site_url/products/' . $item->handle .'</url>': '';
                                $xmlData .= isset($price) ? '<price>' . $price . '</price>' : '';
                                $xmlData .= '<currencyId>RUB</currencyId>';
                                $xmlData .= isset($item->product_type) ? '<categoryId>' . $categoryArr[$item->product_type] . '</categoryId>' : '';
                                $xmlData .= isset($item->images[1]) ? '<picture>' . $item->images[1]->src . '</picture>' : '<picture>' . $item->images[0]->src . '</picture>';
                                $xmlData .= isset($item->product_type) ? '<typePrefix>' . $item->product_type . '</typePrefix>' : '';
                                $xmlData .= isset($item->vendor) ? '<vendor>' . $item->vendor . '</vendor>' : '';
                                $xmlData .= isset($item->title) ? '<model>' . $item->title . '</model>' : '';
                                $xmlData .= isset($item->body_html) ? '<description>' . strip_tags($item->body_html) . '</description>' : '';
                                $xmlData .= '</offer>';
                            }
                        }
                    }

                    $xmlData .= '</offers>';
                    $xmlData .= '</shop>';
                    $xmlData .= '</yml_catalog>';

                    if(file_put_contents($filePath,$xmlData)) {

                        \Yii::info('-------myTarget - xml file updated successfully ------- ', 'debug');
                        echo "XML file updated successfully";

                    } else {
                        echo "error with XML file updating";
                    }

                } else {
                    echo "error with cURL";
                }

            } catch (\Exception $exception) {
                \Yii::info('------------ myTarget - xml file failed -----------------', 'debug');
                \Yii::info($exception->getMessage(), 'debug');
            }
    }

    /**
     * auto update shopifyXML file
     */

    protected function getProducts($since_id=1)
    {
        $productIDs = [];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://" . $this->api_key . ":" . $this->api_pass . "@" . $this->shop . ".myshopify.com/admin/api/2021-01/products.json?limit=250&published_status=published&since_id=" . $since_id,
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

        $responseShopify = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        }

        $response = json_decode($responseShopify)->products;

        if($response) {

            $this->responseProducts[] = $response;

            foreach ($response as $data) {
                $productIDs[] = $data->id;
            }

            $productLastID = max($productIDs);
            $this->getProducts($productLastID);
        }
    }
}
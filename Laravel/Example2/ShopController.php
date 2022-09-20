<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Helpers\MailSwitch;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class ShopController extends BaseController {

  public function exportAllOrders()
  {
      $getAllOrders = DB::table('orders')
          ->leftJoin('order_products', 'orders.id', '=', 'order_products.order_id')
          ->leftJoin('shop_products', 'order_products.product_id', '=', 'shop_products.id')
          ->leftJoin('shop_product_groups', 'shop_product_groups.id', '=', 'shop_products.product_id')
          ->leftJoin('shop_product_categories', 'shop_product_categories.id', '=', 'shop_product_groups.category_id')
          ->leftJoin('users', 'users.id', '=', 'orders.user_id')
          ->leftJoin('user_details', 'user_details.user_id', '=', 'users.id')
          ->leftJoin('countries', 'countries.id', '=', 'user_details.country_id')
          ->select(
              'orders.id',
              'orders.user_id',
              'orders.price_total',
              'orders.delivery_price',
              'orders.payer_info',
              'orders.payment_status',
              'orders.created_at',
              'orders.note',
              'order_products.count',
              'shop_product_groups.product_name',
              'shop_product_categories.title as category_title',
              'users.email',
              'user_details.phone',
              'countries.name as country_name'
          )->get();

      $excelData[] =
          [
              'ID', 'Order', 'Total Price',
              'Count', 'Product Type', 'Client',
              'Email', 'Phone Number', 'Country',
              'Address', 'Delivery Address',
              'Zip code', 'Delivery', 'Created at',
              'Payment Status', 'Note'
          ];

      foreach($getAllOrders->chunk(1000) as $orders) {

          foreach($orders as $order) {

              $payerInfo = json_decode($order->payer_info);
              $payerInfoEncode = json_decode(mb_convert_encoding($order->payer_info, 'UTF-8', 'Windows-1251'));

              $excelData[] = [
                  'id' => $order->id,
                  'productName' => $order->product_name,
                  'totalPrice' => $order->price_total + $order->delivery_price,
                  'count' => $order->count,
                  'categoryTitle' => $order->category_title ? $order->category_title : '',
                  'name' => $order->payer_info && $payerInfo->name ? $payerInfo->name : '',
                  'email' => !is_null($order->email) ? $order->email : ($payerInfo ? $payerInfo->email : ''),
                  'phone' => !is_null($order->phone) ? $order->phone : ($payerInfo ? $payerInfo->phone : ''),
                  'country' => $order->country_name ? $order->country_name : '',
                  'address' => $order->payer_info ? $payerInfoEncode->address : '',
                  'deliveryAddress' => $order->payer_info ? $payerInfoEncode->deliveryAddress : '',
                  'zip' => $order->payer_info ? $payerInfoEncode->zip : '',
                  'deliveryCode' => $order->payer_info ? $payerInfoEncode->deliveryCode : '',
                  'created_at' => $order->created_at,
                  'paymentStatus' => $order->payment_status == 1 ? 'Payed' : 'Not paid',
                  'note' => !is_null($order->note) ? $order->note : '',
              ];
          }
      }

      Excel::create('Orders', function ($excel) use ($excelData) {
          $excel->sheet('orders', function ($sheet) use ($excelData) {
              $sheet->fromArray($excelData, null, 'A1', false, false);
          });
      })->export('xlsx');
  }
}

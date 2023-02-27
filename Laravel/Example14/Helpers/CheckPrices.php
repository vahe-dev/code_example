<?php

namespace App\Helpers;

use App\Models\CartProduct;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\Auth;

class CheckPrices {

    /**
     * To check new prices before payment
     *
     * */
    public static function checkCurrentPrices()
    {
        $userId = '';
        $column = '';

        if (Auth::user()) {
            $userId = Auth::user()->id;
            $column = 'user_id';
        } elseif (isset($_COOKIE['guest_id']) && $_COOKIE['guest_id']) {
            $userId = $_COOKIE['guest_id'];
            $column = 'guest_id';
        }

        $cardProducts = CartProduct::with(['shopProduct', 'productSale'])->where($column, $userId)->get();

        foreach ($cardProducts as $cardProduct) {

            if ($cardProduct->shopProduct->type_id == 2 && $cardProduct->shopProduct->count < 0) {

                CartProduct::where('id', $cardProduct->id)->delete();

            } else {
                if ($cardProduct->price != intval($cardProduct->shopProduct->price)) {

                    if ($cardProduct->productSale && $cardProduct->productSale->is_active == 1) {
                        $pricePerProduct = intval($cardProduct->productSale->price);
                        $priceTotal = $cardProduct->count * $cardProduct->productSale->price;
                    } else {
                        $pricePerProduct = intval($cardProduct->shopProduct->price);
                        $priceTotal = $cardProduct->count * intval($cardProduct->shopProduct->price);
                    }

                    CartProduct::where('id', $cardProduct->id)
                        ->update([
                            'price' => $pricePerProduct,
                            'price_total' => $priceTotal
                        ]);
                } else {
                    if ($cardProduct->productSale && $cardProduct->productSale->is_active == 1) {
                        $pricePerProduct = intval($cardProduct->productSale->price);
                        $priceTotal = $cardProduct->count * $cardProduct->productSale->price;

                        CartProduct::where('id', $cardProduct->id)
                            ->update([
                                'price' => $pricePerProduct,
                                'price_total' => $priceTotal
                            ]);
                    }
                }
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\CheckPrices;
use App\Models\CartProduct;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{

    /**
     * Get basket's page with products and prices
     * */
    public function getBasket()
    {
        $discount_group = 0;

        if (auth()->user()) {
            $this->bucketCount = CartProduct::where('user_id', auth()->user()->id)->count();
        } else if (isset($_COOKIE['guest_id'])) {
            $this->bucketCount = CartProduct::where('guest_id', $_COOKIE['guest_id'])->count();
        } else {
            $this->bucketCount = false;
        }

        if ($this->bucketCount) {
            CheckPrices::checkCurrentPrices();
        }

        if (Auth::user()) {
            $cartAllProducts = CartProduct::with(['product'])
                ->where('user_id', Auth::user()->id)
                ->get();

            foreach ($cartAllProducts as $cartAllProduct) {
                if($cartAllProduct['discount_group'] > 0) {
                    $discount_group = $cartAllProduct['discount_group'];
                    break;
                }
            }
        }else if(isset($_COOKIE['guest_id'])){
            $cartAllProducts = CartProduct::with(['product'])
                ->where('guest_id', $_COOKIE['guest_id'])
                ->get();

            foreach ($cartAllProducts as $cartAllProduct) {
                if($cartAllProduct['discount_group'] > 0) {
                    $discount_group = $cartAllProduct['discount_group'];
                    break;
                }
            }
        }
        else{
            $cartAllProducts  = [];
        }
        $totalPrice = $totalWeight = 0;
        foreach ($cartAllProducts as $total) {
            $totalPrice += $total['price_total'];
            $totalWeight += $total['count']*$total['product']['weight'];
        }
        $data = [
            'forViewContent' => true,
            'categories' => $this->categories,
            'bucketCount' => $this->bucketCount,
            'shopTopBanner' => $this->shopTopBanner,
            'shopLeftBanner' => $this->shopLeftBanner,
            'cartAllProducts' => $cartAllProducts,
            'totalPrice' => $totalPrice,
            'totalWeight' => $totalWeight,
            'discount_group' => $discount_group
        ];
        return view('store')->with($data);
    }
}

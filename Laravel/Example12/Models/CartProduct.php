<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartProduct extends Model
{
    public function shopProduct(){
        return $this->belongsTo('App\Models\ShopProduct', 'product_id')->select('id', 'price', 'count', 'type_id');
    }

    public function productSale(){
        return $this->belongsTo('App\Models\Sale', 'product_id', 'product_id');
    }
}

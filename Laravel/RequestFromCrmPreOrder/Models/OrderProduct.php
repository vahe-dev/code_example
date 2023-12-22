<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    public function product(){
        return $this->belongsTo('App\Models\ShopProduct', 'product_id')->with(['importProduct','type', 'category', 'group',]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\GetB2cpl;

class StoreController extends Controller
{
    public function getZipData()
    {
        if (request('otherCountryId')) {
            $code = request('otherCountryId');
            $weight = (int)request('totalWeight');
            $calculate = json_decode(file_get_contents('http://tariff.russianpost.ru/tariff/v1/calculate?json&object=7031&country='.$code.'&weight='.$weight.'&service=10'));
            if(!empty($calculate->error)){
                return 'error';
            } else {
                $price = substr($calculate->ground->valnds, 0, -2);
                return $price.'.00';
            }

        }else{
            $b2cpl = new GetB2cpl();
            return $b2cpl->getDeliveryZipData(request('curIndex'), trim(request('totalWeight')));
        }
    }
}
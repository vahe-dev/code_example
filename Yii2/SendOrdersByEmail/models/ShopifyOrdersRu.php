<?php

namespace common\models;

/**
 * Class ShopifyOrdersRu
 * @package common\models
 */

class ShopifyOrdersRu extends \common\models\ex\AxModel
{
    public static function tableName()
    {
        return 'shopify_orders_ru';
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id'],  'integer'];
        $rules[] = [['email','orders_date','order_id'],  'string', 'max' => 100];
        $rules[] = [['created_at','updated_at'], 'date', 'format' => 'php:Y-m-d H:i:s'];
        return $rules;

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'email' => 'email',
            'orders_date' => 'orders date',
        ];
    }


}


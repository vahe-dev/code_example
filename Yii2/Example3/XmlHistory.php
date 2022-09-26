<?php

namespace common\models;

/**
 * Class XmlHistory
 */

class XmlHistory
{
    public static function tableName(): string
    {
        return 'xml_history';
    }

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id','order_id'],  'integer'];
        $rules[] = [['xml'],  'string'];
        $rules[] = [['order_id', 'xml'], 'required'];
        return $rules;

    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'xml' => 'xml',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
        ];
    }
}

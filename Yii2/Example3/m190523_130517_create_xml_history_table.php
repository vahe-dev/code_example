<?php

use yii\db\Migration;

/**
 * Handles the creation of table `xml_history`.
 */
class m190523_130517_create_xml_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('xml_history', [
            'id' => $this->primaryKey(10),
            'order_id' => $this->integer(20),
            'xml' => $this->text(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->timestamp(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('xml_history');
    }
}
<?php

use yii\db\Migration;

/**
 * Class m180628_131720_convert_to_utf8mb4
 */
class m180628_131720_convert_to_utf8mb4 extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute(file_get_contents(__DIR__ . '/utf8mb4.sql')); 
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180628_131720_convert_to_utf8mb4 cannot be reverted.\n";

        return false;
    }
}

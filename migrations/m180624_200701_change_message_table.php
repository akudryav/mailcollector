<?php

use yii\db\Migration;

/**
 * Class m180624_200701_change_message_table
 */
class m180624_200701_change_message_table extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->addColumn('message', 'label', $this->string());
        $this->addColumn('message', 'language', $this->string());
    }

    public function down()
    {
        $this->dropColumn('message', 'label');
        $this->dropColumn('message', 'language');
    }

}

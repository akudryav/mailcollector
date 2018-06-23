<?php

use yii\db\Migration;

/**
 * Class m180623_085405_change_mailbox_message_tables
 */
class m180623_085405_change_mailbox_message_tables extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->addColumn('message', 'full_id', $this->string());
        $this->addColumn('mailbox', 'check_time', $this->integer());
    }

    public function down()
    {
        $this->dropColumn('message', 'full_id');
        $this->dropColumn('mailbox', 'check_time');
    }

}

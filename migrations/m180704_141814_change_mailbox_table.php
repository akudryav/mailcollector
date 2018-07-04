<?php

use yii\db\Migration;

/**
 * Class m180704_141814_change_mailbox_table
 */
class m180704_141814_change_mailbox_table extends Migration
{
   
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->addColumn('mailbox', 'backup_email', $this->string());
    }

    public function down()
    {
        $this->dropColumn('mailbox', 'backup_email');
    }
    
}

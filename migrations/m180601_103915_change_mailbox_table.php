<?php

use yii\db\Migration;

/**
 * Class m180601_103915_change_mailbox_table
 */
class m180601_103915_change_mailbox_table extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->alterColumn('mailbox', 'host', $this->string());
        $this->renameColumn('mailbox', 'host', 'buyer');
        $this->alterColumn('mailbox', 'port', 'varchar(20)');
        $this->renameColumn('mailbox', 'port', 'phone');
        
        $this->update('mailbox', ['buyer'=>null, 'phone' => null]);
    }

    public function down()
    {
        $this->renameColumn('mailbox', 'buyer', 'host');
        $this->renameColumn('mailbox', 'phone', 'port');
    }
}

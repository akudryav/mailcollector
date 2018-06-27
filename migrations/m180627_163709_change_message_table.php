<?php

use yii\db\Migration;

/**
 * Class m180627_163709_change_message_table
 */
class m180627_163709_change_message_table extends Migration
{
    public function up()
    {
        $this->addColumn('message', 'mailer', $this->string());
        $this->addColumn('message', 'ip_type', $this->string(10));
        $this->dropColumn('mailbox', 'last_message_uid');
    }

    public function down()
    {
        $this->dropColumn('message', 'mailer');
        $this->dropColumn('message', 'ip_type');
        $this->addColumn('mailbox', 'last_message_uid', $this->integer());
    }
}

<?php

use yii\db\Migration;

/**
 * Class m180616_085927_change_token_mailbox_tables
 */
class m180616_085927_change_token_mailbox_tables extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->dropColumn('mailbox', 'is_ssl');
        $this->addColumn('token', 'credfile', $this->string());
    }

    public function down()
    {
        $this->addColumn('mailbox', 'is_ssl', $this->boolean()->notNull()->defaultValue(1));
        $this->dropColumn('token', 'credfile');
    }

}

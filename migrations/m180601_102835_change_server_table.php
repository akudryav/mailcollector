<?php

use yii\db\Migration;

/**
 * Class m180601_102835_change_server_table
 */
class m180601_102835_change_server_table extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->renameColumn('server', 'host', 'imap');
        $this->renameColumn('server', 'name', 'host');
    }

    public function down()
    {
        $this->renameColumn('server', 'host', 'name');
        $this->renameColumn('server', 'imap', 'host');
    }
    
}

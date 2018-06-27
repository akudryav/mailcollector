<?php

use yii\db\Migration;

/**
 * Class m180627_172255_change_server_table
 */
class m180627_172255_change_server_table extends Migration
{
    public function up()
    {
        $this->addColumn('server', 'spam_folder', $this->string());
        $this->update('server', ['spam_folder'=>'{imap.aol.com:993/ssl}Spam'], ['host'=>'aol.com']);
        $this->update('server', ['spam_folder'=>'{imap.mail.yahoo.com:993/ssl}Bulk Mail'], ['host'=>'yahoo.com']);
        $this->update('server', ['spam_folder'=>'{imap-mail.outlook.com:993/ssl}Junk'], ['host'=>'outlook.com']);
        $this->update('server', ['spam_folder'=>'Spam'], ['host'=>'gmail.com']);
    }

    public function down()
    {
        $this->dropColumn('server', 'spam_folder');
    }
}

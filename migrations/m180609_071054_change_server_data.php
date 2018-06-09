<?php

use yii\db\Migration;

/**
 * Class m180609_071054_change_server_data
 */
class m180609_071054_change_server_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update('server', ['host'=>'gmail.com'], ['host'=>'Gmail']);
        $this->update('server', ['host'=>'yahoo.com'], ['host'=>'Yahoo']);
        $this->update('server', ['host'=>'outlook.com'], ['host'=>'Outlook']);
        $this->update('server', ['host'=>'aol.com'], ['host'=>'AOL']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180609_071054_change_server_data cannot be reverted.\n";

        return false;
    }

}

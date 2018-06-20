<?php

use yii\db\Migration;

/**
 * Class m180620_055255_change_token_table
 */
class m180620_055255_change_token_table extends Migration
{
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        $this->renameColumn('token', 'credfile', 'secret_file');
        $this->renameColumn('token', 'id_token', 'access_token');
        $this->dropColumn('token', 'secret_token');
        $this->alterColumn('token', 'access_token', $this->text());
    }

    public function down()
    {
        $this->addColumn('token', 'secret_token', $this->string());
        $this->renameColumn('token', 'secret_file', 'credfile');
        $this->renameColumn('token', 'access_token', 'id_token');
    }
   
}

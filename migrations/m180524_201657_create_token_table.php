<?php

use yii\db\Migration;

/**
 * Handles the creation of table `token`.
 */
class m180524_201657_create_token_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('token', [
            'id' => $this->primaryKey(),
            'mailbox_id' => $this->integer()->notNull(),
            'id_token' => $this->string(),
            'secret_token' => $this->string(),
        ]);

        // add foreign key for table `messages`
        $this->addForeignKey(
            'fk-token-mailbox_id',
            'token',
            'mailbox_id',
            'mailbox',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('token');
    }
}

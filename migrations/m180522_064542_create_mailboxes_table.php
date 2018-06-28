<?php

use yii\db\Migration;

/**
 * Handles the creation of table `mailbox`.
 */
class m180522_064542_create_mailboxes_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('mailbox', [
            'id' => $this->primaryKey(),
            'email' => $this->string(191)->notNull()->unique(),
            'password' => $this->string(191)->notNull(),
            'host' => $this->string(191)->notNull(),
            'port' => $this->string(10),
            'is_ssl' => $this->boolean()->notNull()->defaultValue(1),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(0),
            'last_message_uid' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('mailbox');
    }
}

<?php

use yii\db\Migration;

/**
 * Handles the creation of table `message`.
 */
class m180522_070320_create_messages_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('message', [
            'id' => $this->primaryKey(),
            'mailbox_id' => $this->integer()->notNull(),
            'uid' => $this->integer()->notNull(),
            'from_ip' => $this->string(),
            'from_domain' => $this->string(),
            'subject' => $this->string(),
            'body_text' => $this->text(),
            'body_html' => $this->text(),
            'attachment_count' => $this->integer()->notNull()->defaultValue(0),
            'header' => $this->text(),
            'message_date' => $this->dateTime(),
            'create_date' => $this->dateTime(),
            'modify_date' => $this->dateTime(),
            'is_ready' => $this->boolean()->notNull()->defaultValue(0),        
        ]);
        
        // add foreign key for table `messages`
        $this->addForeignKey(
            'fk-message-mailbox_id',
            'message',
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
        $this->dropTable('message');
    }
}

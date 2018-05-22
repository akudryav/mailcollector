<?php

use yii\db\Migration;

/**
 * Handles the creation of table `attachment`.
 */
class m180522_072920_create_attachments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('attachment', [
            'id' => $this->primaryKey(),
            'message_id' => $this->integer()->notNull(),
            'file_name' => $this->string()->notNull(),
            'mime_type' => $this->string()->notNull(),
            'file_size' => $this->integer()->notNull(),
            'location' => $this->string()->notNull(),
        ]);
        
        // add foreign key for table `attachment`
        $this->addForeignKey(
            'fk-attachment-message_id',
            'attachment',
            'message_id',
            'message',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('attachment');
    }
}

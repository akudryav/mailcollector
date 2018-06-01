<?php

use yii\db\Migration;

/**
 * Handles the creation of table `server`.
 */
class m180524_200852_create_server_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('server', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'host' => $this->string()->notNull(),
            'port' => $this->string(10)->defaultValue('993'),
            'is_ssl' => $this->boolean()->notNull()->defaultValue(1),
        ]);
        // добавляем базовый набор
        $this->insert('server', [
            'name' => 'gmail.com',
            'host' => 'imap.gmail.com',
            'port' => '993',
            'is_ssl' => 1,
        ]);
        $this->insert('server', [
            'name' => 'yahoo.com',
            'host' => 'imap.mail.yahoo.com',
            'port' => '993',
            'is_ssl' => 1,
        ]);
        $this->insert('server', [
            'name' => 'outlook.com',
            'host' => 'imap-mail.outlook.com',
            'port' => '993',
            'is_ssl' => 1,
        ]);
        $this->insert('server', [
            'name' => 'aol.com',
            'host' => 'imap.aol.com',
            'port' => '993',
            'is_ssl' => 1,
        ]);


        $this->addColumn('mailbox', 'server_id', $this->integer());

        // add foreign key for table `mailbox`
        $this->addForeignKey(
            'fk-mailbox-server_id',
            'mailbox',
            'server_id',
            'server',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-mailbox-server_id', 'mailbox');
        $this->dropTable('server');
    }
}

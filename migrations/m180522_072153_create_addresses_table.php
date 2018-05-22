<?php

use yii\db\Migration;

/**
 * Handles the creation of table `address`.
 */
class m180522_072153_create_addresses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('address', [
            'id' => $this->primaryKey(),
            'message_id' => $this->integer()->notNull(),
            'type' => $this->string(10)->notNull(),
            'name' => $this->string(),
            'email' => $this->string(),
        ]);
        
        // add foreign key for table `address`
        $this->addForeignKey(
            'fk-address-message_id',
            'address',
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
        $this->dropTable('address');
    }
}

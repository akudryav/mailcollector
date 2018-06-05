<?php

use yii\db\Migration;

/**
 * Handles the creation of table `vertical`.
 */
class m180605_082312_create_vertical_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('vertical', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
        ]);
        
        $this->addColumn('mailbox', 'vertical_id', $this->integer());
        // add foreign key for table `mailbox`
        $this->addForeignKey(
            'fk-mailbox-vertical_id',
            'mailbox',
            'vertical_id',
            'vertical',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('mailbox', 'vertical_id');
        $this->dropTable('vertical');
    }
}

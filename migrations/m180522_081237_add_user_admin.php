<?php

use yii\db\Migration;
use yii\db\Transaction;
use app\models\User;

/**
 * Class m180522_081237_add_user_admin
 */
class m180522_081237_add_user_admin extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $transaction = $this->getDb()->beginTransaction();
        $user = \Yii::createObject([
            'class'    => User::className(),
            'scenario' => 'create',
            'username' => 'admin',
            'email'    => 'admin@example.com',      
            'password' => '123123qW',
        ]);
        if (!$user->insert(false)) {
            $transaction->rollBack();
            return false;
        }
        $transaction->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('user', ['username' => 'admin']);
    }

}

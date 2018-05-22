<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "mailbox".
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $host
 * @property string $port
 * @property int $is_ssl
 * @property int $is_deleted
 * @property int $last_message_uid
 *
 * @property Message[] $messages
 */
class Mailbox extends \yii\db\ActiveRecord
{
    public static $yes_no = ['Нет', 'Да'];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mailbox';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'password', 'host'], 'required'],
            [['is_ssl', 'is_deleted', 'last_message_uid'], 'integer'],
            [['email', 'password', 'host'], 'string', 'max' => 255],
            [['port'], 'string', 'max' => 10],
            ['is_ssl', 'default', 'value' => 1],
            ['is_deleted', 'default', 'value' => 0],
            [['email'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'password' => 'Пароль',
            'host' => 'Сервер входящей почты (IMAP)',
            'port' => 'Порт сервера',
            'is_ssl' => 'Нужен SSL',
            'is_deleted' => 'Аккаунт удален',
            'last_message_uid' => 'Uid Последнего сообщения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Message::className(), ['mailbox_id' => 'id']);
    }
}

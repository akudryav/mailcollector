<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
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
            [['is_ssl', 'is_deleted', 'last_message_uid', 'server_id'], 'integer'],
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
            'server_id' => 'Почтовый сервер',
            'host' => 'Сервер входящей почты (IMAP)',
            'port' => 'Порт сервера',
            'is_ssl' => 'Нужен SSL',
            'is_deleted' => 'Аккаунт блокирован',
            'last_message_uid' => 'Uid Последнего сообщения',
        ];
    }
    
    public function statusName()
    {
        return isset(self::$yes_no[$this->is_deleted]) ? self::$yes_no[$this->is_deleted] : 'unknown';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Message::className(), ['mailbox_id' => 'id']);
    }
      /**
     * получение списка ящиков по которым есть незагруженные письма
     */
    public static function getUnloaded()
    {
        $array = self::find()
        ->select(['mailbox.id'])
        ->joinWith('messages', false)
        ->where(['message.is_ready' => 0, 'mailbox.is_deleted' => 0])
        ->groupBy(['mailbox.id'])
        ->asArray()
        ->all();
        return ArrayHelper::getColumn($array, 'id');
    }
}

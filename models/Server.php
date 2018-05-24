<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "server".
 *
 * @property int $id
 * @property string $name
 * @property string $host
 * @property string $port
 * @property int $is_ssl
 *
 * @property Mailbox[] $mailboxes
 */
class Server extends \yii\db\ActiveRecord
{
    public static $yes_no = ['Нет', 'Да'];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'server';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'host'], 'required'],
            [['is_ssl'], 'integer'],
            [['name', 'host'], 'string', 'max' => 255],
            [['port'], 'string', 'max' => 10],
            [['name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'host' => 'Host',
            'port' => 'Port',
            'is_ssl' => 'Is Ssl',
        ];
    }

    public function statusName()
    {
        return isset(self::$yes_no[$this->is_ssl]) ? self::$yes_no[$this->is_ssl] : 'unknown';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMailboxes()
    {
        return $this->hasMany(Mailbox::className(), ['server_id' => 'id']);
    }
}

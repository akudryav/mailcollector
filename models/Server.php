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
            [['imap', 'host'], 'required'],
            [['is_ssl'], 'integer'],
            [['imap', 'host'], 'string', 'max' => 255],
            [['port'], 'string', 'max' => 10],
            [['host'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'imap' => 'Сервер IMAP',
            'host' => 'Почтовый домен',
            'port' => 'Порт IMAP',
            'is_ssl' => 'Нужен SSL',
        ];
    }
    
    public static function findIdByMail($email)
    {
        $host = substr(strrchr($email, "@"), 1);
        $model = self::find()
            ->where(['host' => $host])
            ->one();
        
        if ($model !== null) {
            return $model->id;
        }
        return false;
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

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
            [['imap', 'host', 'spam_folder'], 'string', 'max' => 255],
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
            'spam_folder' => 'Строка подключения к папке спама',
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMailboxes()
    {
        return $this->hasMany(Mailbox::className(), ['server_id' => 'id']);
    }
}

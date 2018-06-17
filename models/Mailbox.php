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
            [['email', 'password'], 'required'],
            [['is_deleted', 'last_message_uid'], 'integer'],
            [['email', 'password', 'buyer', 'phone'], 'string', 'max' => 255],
            ['is_deleted', 'default', 'value' => 0],
            [['email'], 'unique'],
            ['server_id', 'exist', 'targetClass' => Server::className(), 'targetAttribute' => 'id'],
            ['vertical_id', 'filter', 'filter' => [Vertical::className(), 'processVertical']],
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
            'buyer' => 'Buyer name', 
            'phone' => 'Телефон для подтверждений',
            'is_deleted' => 'Аккаунт блокирован',
            'vertical_id' => 'Вертикаль',
            'last_message_uid' => 'Uid Последнего сообщения',
        ];
    }
    
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->server_id = Server::findIdByMail($this->email);
        if(!$this->server_id) {
            $this->addError('email', 'Не найден почтовый сервер для '.$this->email);
            return false;
        }
        return true;
    }
    
    public static function findByMail($email)
    {
        return self::find()
            ->where(['email' => trim($email)])
            ->one();
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
     * @return \yii\db\ActiveQuery
     */
    public function getServer()
    {
        return $this->hasOne(Server::className(), ['id' => 'server_id']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVertical()
    {
        return $this->hasOne(Vertical::className(), ['id' => 'vertical_id']);
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

    public function needCredential()
    {
        if($this->server->host != 'gmail.com') return false;
        $token = Token::findOne(['mailbox_id' => $this->id]);
        if ($token == null) return true;
        return !is_file(Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR . $token->credfile);
    }
}

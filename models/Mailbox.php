<?php

namespace app\models;

use Yii;
use yii\helpers\Html;
/**
 * This is the model class for table "mailbox".
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property int $is_deleted
 * @property int $check_time
 *
 * @property Message[] $messages
 */
class Mailbox extends \yii\db\ActiveRecord
{
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
            [['is_deleted', 'check_time'], 'integer'],
            [['email', 'password', 'buyer', 'backup_email'], 'string', 'max' => 255],
            ['is_deleted', 'default', 'value' => 0],
            [['email'], 'unique'],
            ['phone', 'string', 'length' => [4, 20]],
            ['server_id', 'exist', 'targetClass' => Server::className(), 'targetAttribute' => 'id'],
            ['vertical_id', 'filter', 'filter' => [Vertical::className(), 'processVertical']],
            ['user_id', 'exist', 'targetClass' => User::className(), 'targetAttribute' =>  'id'],
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
            'backup_email' => 'Резервный Email',
            'password' => 'Пароль',
            'server_id' => 'Почтовый провайдер',
            'buyer' => 'Покупатель', 
            'phone' => 'Телефон для подтверждений',
            'is_deleted' => 'Аккаунт блокирован',
            'vertical_id' => 'Вертикаль',
            'check_time' => 'Время последней проверки',
        ];
    }
    
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->server_id = Server::findIdByMail($this->email);
        if(!$this->server_id) {
            $this->addError('email', 'Не найден почтовый провайдер для '.$this->email);
            return false;
        }
        if ($insert) {
            $this->user_id = Yii::$app->user->identity->id;
        }
        return true;
    }
    
    public static function findByMail($email)
    {
        return self::find()
            ->where(['email' => trim($email)])
            ->one();
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
     * получение списка ящиков для протокола IMAP
     */
    public static function getImap()
    {
        return self::find()
        ->joinWith('server', false)
        ->where(['is_deleted' => 0])
        ->andWhere(['<>','server.host', 'gmail.com'])
        ->all();
    }

    /**
     * получение списка ящиков для протокола GMAIL
     */
    public static function getGmail()
    {
        return self::find()
            ->joinWith('server', false)
            ->where(['is_deleted' => 0])
            ->andWhere(['server.host' => 'gmail.com'])
            ->all();
    }

    public function getMaxUid($label = 'inbox')
    {
        return $this->getMessages()->where(['label' => $label])->max('uid');
    }

    public function tokenUrl()
    {
        if($this->server->host != 'gmail.com') return false;
        $token = Token::findOne(['mailbox_id' => $this->id]);
        if($token == null || empty($token->access_token)) {
            return Html::a('<span class="glyphicon glyphicon-text-width"></span>', ['mailbox/token', 'id' => $this->id], [
                'title' => 'Получить токен',
                'class' => 'text-warning', 
                ]);
        }
    }
}

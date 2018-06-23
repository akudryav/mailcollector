<?php

namespace app\models;

use Yii;
use yii\helpers\Html;

/**
 * This is the model class for table "message".
 *
 * @property int $id
 * @property int $mailbox_id
 * @property int $uid
 * @property string $from_ip
 * @property string $from_domain
 * @property string $subject
 * @property string $body_text
 * @property string $body_html
 * @property int $attachment_count
 * @property string $header
 * @property string $message_date
 * @property string $create_date
 * @property string $modify_date
 * @property int $is_ready
 *
 * @property Address[] $addresses
 * @property Attachment[] $attachments
 * @property Mailbox $mailbox
 */
class Message extends \yii\db\ActiveRecord
{
    public static $yes_no = ['Нет', 'Да'];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mailbox_id', 'uid'], 'required'],
            [['mailbox_id', 'uid', 'attachment_count', 'is_ready'], 'integer'],
            [['body_text', 'body_html', 'header', 'full_id'], 'string'],
            [['message_date', 'create_date', 'modify_date'], 'safe'],
            [['from_ip', 'from_domain', 'subject'], 'string', 'max' => 255],
            [['mailbox_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mailbox::className(), 'targetAttribute' => ['mailbox_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mailbox_id' => 'Почтовый Аккаунт',
            'uid' => 'Uid',
            'full_id' => 'Полный ID сообщения',
            'from_ip' => 'Ip отправителя',
            'from_domain' => 'Домен отправителя',
            'subject' => 'Тема',
            'body_text' => 'Текст',
            'body_html' => 'Html',
            'attachment_count' => 'Число вложений',
            'header' => 'Заголовки',
            'message_date' => 'Дата получения',
            'create_date' => 'Дата загрузки',
            'modify_date' => 'Дата изменения',
            'is_ready' => 'Загружено полностью',
        ];
    }
    
    public function statusName()
    {
        return isset(self::$yes_no[$this->is_ready]) ? self::$yes_no[$this->is_ready] : 'unknown';
    }
    
    public function showAttachments()
    {
        $list = [];
        foreach ($this->attachments as $r) {
            $list[] = Html::a($r->file_name, ['site/download', 'filename' => $r->location]);
        }
        return implode('<br>', $list);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Address::className(), ['message_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::className(), ['message_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMailbox()
    {
        return $this->hasOne(Mailbox::className(), ['id' => 'mailbox_id']);
    }
}
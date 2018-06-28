<?php

namespace app\models;

use Yii;
use yii\helpers\Html;
use LanguageDetection\Language;

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
    const LANG_LIST = ['de', 'en', 'fr', 'ru', 'it', 'es', 'nl'];

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
            [['body_text', 'body_html', 'header', 'full_id', 'label', 'language', 'mailer', 'ip_type'], 'string'],
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
            'label' => 'Метка',
            'language' => 'Язык',
            'mailer' => 'Система рассылки',
            'ip_type' => 'Тип ip',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            $this->detectLang();
            $this->detectIP();
            $this->detectMailer();

            return true;
        }
        return false;
    }
    //определение типа ip
    private function detectIP()
    {
        // пытаемся найти ip отправителя
        $regex='/client\-ip\=(.+?)\;/s';
        if(preg_match($regex, $this->header, $matches)){
            $this->from_ip = $matches[1];
        }

        if(empty($this->from_ip)) {
            $regex = '/Received:.*\[([^\]]*)\]/';
            if(preg_match_all($regex, $this->header, $matches)){
                $this->from_ip = end($matches[1]);
            }
        }

        if(empty($this->from_ip)) return;

        if(filter_var($this->from_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->ip_type = 'IPv4';
        }
        if(filter_var($this->from_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->ip_type = 'IPv6';
        }
    }
    
    //определение мейлера
    private function detectMailer()
    {
        $mailers = ['mailchimp', 'sendgrid', 'amazon'];
        foreach ($mailers as $url) {
            if (stripos($this->header, $url) !== FALSE) {
                $this->mailer = $url;
                return;
            }
        }
    }

    //Подбор языка сообщения
    private function detectLang()
    {
        $ld = new Language(self::LANG_LIST);
        $text = !empty($this->body_text) ? strip_tags($this->body_text) : strip_tags($this->body_html);
        $this->language = (string)$ld->detect($text);
    }

    public function showAddresses()
    {
        $list = [];
        foreach ($this->addresses as $r) {
            $list[] = $r->type.': '.$r->name.' '.$r->email;
        }
        return implode('<br>', $list);
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
<?php

namespace app\models;

use Yii;
use google\apiclient;

/**
 * This is the model class for table "token".
 *
 * @property int $id
 * @property int $mailbox_id
 * @property string $access_token
 * @property string $secret_file
 *
 * @property Mailbox $mailbox
 */
class Token extends \yii\db\ActiveRecord
{
    const APP_JSON_FILE = 'client_secret_SA.json';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mailbox_id'], 'required'],
            [['mailbox_id'], 'integer'],
            [['access_token'], 'safe'],
            [['secret_file'], 'file', 'skipOnEmpty' => true, 'checkExtensionByMimeType' => false, 'extensions' => 'json'],
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
            'mailbox_id' => 'Почтовый аккаунт',
            'access_token' => 'Токен доступа',
            'secret_file' => 'Идентификатор клиента OAuth 2.0 (json файл)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMailbox()
    {
        return $this->hasOne(Mailbox::className(), ['id' => 'mailbox_id']);
    }
    /**
     * загрузка файла
     */
    public function upload()
    {
        if ($this->validate()) {
            return $this->secret_file->saveAs(Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR .
                $this->secret_file->baseName . '.' . $this->secret_file->extension);
        } else {
            return false;
        }
    }
    /**
     * Получение клиента ГУГЛ АПИ
     */
    public static function getClient()
    {
        $json = Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR . self::APP_JSON_FILE;
        $client = new \Google_Client();
        $client->setApplicationName('Spam Analytics');
        $client->setScopes(\Google_Service_Gmail::GMAIL_READONLY);
        $client->setAuthConfig($json);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        return $client;
    }
    
}

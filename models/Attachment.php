<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "attachment".
 *
 * @property int $id
 * @property int $message_id
 * @property string $file_name
 * @property string $mime_type
 * @property int $file_size
 * @property string $location
 *
 * @property Message $message
 */
class Attachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_id', 'file_name', 'mime_type', 'file_size', 'location'], 'required'],
            [['message_id', 'file_size'], 'integer'],
            [['file_name', 'mime_type', 'location'], 'string', 'max' => 255],
            [['message_id'], 'exist', 'skipOnError' => true, 'targetClass' => Message::className(), 'targetAttribute' => ['message_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_id' => 'Message ID',
            'file_name' => 'File Name',
            'mime_type' => 'Mime Type',
            'file_size' => 'File Size',
            'location' => 'Location',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMessage()
    {
        return $this->hasOne(Message::className(), ['id' => 'message_id']);
    }
}

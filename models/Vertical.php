<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "vertical".
 *
 * @property int $id
 * @property string $name
 */
class Vertical extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vertical';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
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
            'name' => 'Вертикаль',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMailboxes()
    {
        return $this->hasMany(Mailbox::className(), ['vertical_id' => 'id']);
    }

    public static function userList()
    {
        $query = Vertical::find()->select('vertical.*');

        if(!Yii::$app->user->identity->isAdmin()) {
            $query->joinWith('mailboxes')
                ->addSelect('COUNT(mailbox.id) AS boxCount')
                ->where(['mailbox.user_id' => Yii::$app->user->identity->id])
                ->groupBy('vertical.id');
        }
        return $query;
    }
    
    public static function processVertical($vertical = null)
    {
        if (null == $vertical) return null;
        if (ctype_digit($vertical)) {
            // проверка наличия тогого id
            $model = self::findOne($vertical);
            if (null != $model)
                return (int)$vertical;
        }
        // ищем по строке
        $model = self::find()->where(['name' => $vertical])->one();
        if (null != $model) return $model->id;
        // если нет добавляем 
        $model = new Vertical();
        $model->name = $vertical;
        $model->save();
        return $model->id;
    }
}

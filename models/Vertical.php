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
    
    public static function processVertical($vertical = null)
    {
        if (null == $vertical) return null;
        // целое
        if (ctype_digit($vertical)) return (int)$vertical;
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

<?php

/* 
 * Модель загрузки CSV файла
 */

namespace app\models;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $csvFile;

    public function rules()
    {
        return [
            [['csvFile'], 'file', 'checkExtensionByMimeType' => false, 'extensions' => 'csv'],
        ];
    }
    
    public function upload()
    {
        if ($this->validate()) {
            $path = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $this->csvFile->baseName . '.' . $this->csvFile->extension;
            $this->csvFile->saveAs($path);
            return $path;
        } else {
            return false;
        }
    }
}
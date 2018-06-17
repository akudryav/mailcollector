<?php
/* 
 * Модель загрузки Json файла
 */

namespace app\models;
use Yii;
use yii\base\Model;

class JsonUploadForm extends Model
{
    public $jsonFile;

    public function rules()
    {
        return [
            [['jsonFile'], 'file', 'skipOnEmpty' => false, 
                'checkExtensionByMimeType' => false, 'extensions' => 'json'],
        ];
    }
    
    public function upload()
    {
        if ($this->validate()) {
            return $this->jsonFile->saveAs(Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR .
                $this->jsonFile->baseName . '.' . $this->jsonFile->extension);
        } else {
            return false;
        }
    }
}
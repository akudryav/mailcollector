<?php
/* 
 * Модель загрузки CSV файла
 */

namespace app\models;
use Yii;
use yii\base\Model;

class CsvUploadForm extends Model
{
    public $csvFile;
    public $vertical;
    public $path;

    public function rules()
    {
        return [
            [['csvFile'], 'file', 'skipOnEmpty' => false, 
                'checkExtensionByMimeType' => false, 'extensions' => 'csv'],
            [['vertical'], 'string', 'max' => 255],
        ];
    }
    
    public function upload()
    {
        if ($this->validate()) {
            $this->path = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $this->csvFile->baseName . '.' . $this->csvFile->extension;
            return $this->csvFile->saveAs($this->path);
        } else {
            return false;
        }
    }
    
    /** 
    * @param string $csvFile Path to the CSV file
    * @return string Delimiter
    */
    public function detectDelimiter()
    {
        $delimiters = array(
            ';' => 0,
            ',' => 0,
            "\t" => 0,
            "|" => 0
        );

        $handle = fopen($this->path, "r");
        $firstLine = fgets($handle);
        fclose($handle); 
        foreach ($delimiters as $delimiter => &$count) {
            $count = count(str_getcsv($firstLine, $delimiter));
        }

        return array_search(max($delimiters), $delimiters);
    }
}
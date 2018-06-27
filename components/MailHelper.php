<?php
namespace app\components;

use Yii;

class MailHelper {

    public static $yes_no = ['Нет', 'Да'];

    //Функция для получения пути к директории, где будут храниться файлы.
    //Файлы будут сохраняться в поддиректории, созданной по
    //текущей дате. Например, 2014-07-31. Это позволит
    //не держать файлы в одной директории. Много файлов в
    //одной директории замедляет чтение директории
    public static function getStoreDirectory()
    {
        $date_folder = Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR;
        if(!file_exists($date_folder)) mkdir($date_folder);
        return $date_folder;
    }

    //получаем расширение файла
    public static function getFileExtension($filename)
    {
        $arr = explode(".",$filename);
        return count($arr) > 1 ? "." . end($arr) : "";
    }

    //перевести текст в дату MySQL
    public static function strToMysqlDate($text)
    {
        if(null == $text) return null;
        $unixTimestamp=strtotime($text);
        return date("Y-m-d H:i:s", $unixTimestamp);
    }

    public static function yesOrNo($value)
    {
        return isset(self::$yes_no[$value]) ? self::$yes_no[$value] : 'unknown';
    }
}
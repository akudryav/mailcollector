<?php
namespace app\components;

use Yii;

class MailHelper {

    public static function makeConnection($account)
    {
        if('gmail.com' == $account->server->host) {
            return new GmailConnection([
                'mailbox_id' => $account->id,
                'email' => $account->email,
            ]);
        } else {
            // получаем данные почтового сервера
            $server = $account->server;
            //если подключение идет через SSL,
            //то достаточно добавить "/ssl" к строке подключения, и
            //поддержка SSL будет включена
            $ssl = $server->is_ssl ? "/ssl" : "";

            // конфигурация  подключения
            $config = [
                'imapPath' => "{{$server->imap}:{$server->port}{$ssl}}",
                'imapLogin' => $account->email,
                'imapPassword' => $account->password,
            ];
            return new ImapConnection($config);
        }
    }

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
}
<?php

namespace app\components;

use Yii;

class Imap extends \yii\base\Model {

//получение заголовка письма в виде объекта
public function getHeader($mbox,$uid){
    return imap_rfc822_parse_headers(getHeaderRaw($mbox,$uid));
}
 
//получение заголовка письма
public function getHeaderRaw($mbox,$uid){
    return imap_fetchbody($mbox, $uid, '0', FT_UID);
}
 
//раскодировка заголовка
public function getDecodedHeader($text){
    //imap_mime_header_decode - декодирует элементы MIME-шапки в виде массива
    //У каждого элемента указана кодировка(charset) и сам текст(text)
    $elements = imap_mime_header_decode($text);
    $ret = "";
    //перебираем элементы
    for ($i=0; $i<count($elements); $i++) {
        $charset = $elements[$i]->charset;//кодировка
        $text = $elements[$i]->text;//закодированный текст
        if($charset == 'default'){
            //если элемент не кодирован, то значение кодировки default
            $ret .= $text;
        }else{
            //приводим всё кодировке к UTF-8
            $ret .= iconv($charset,"UTF-8",$text);
        }
    }
    return $ret;
}
 
//получение содержимого письма в виде простого текста
public function getTextBody($imap,$uid){
    return getPart($imap, $uid, "TEXT/PLAIN");
}
 
//получение содержимого письма в виде формате html
public function getHtmlBody($imap,$uid){
    return getPart($imap, $uid, "TEXT/HTML");
}
 
//получение части письма
public function getPart($imap, $uid, $mimetype) {
    //получение структуры письма
    $structure = imap_fetchstructure($imap, $uid, FT_UID);
    if ($structure) {
        if ($mimetype == getMimeType($structure)) {
            $partNumber = 1;
            //imap_fetchbody - извлекает определённый раздел тела сообщения
            $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
 
            $charset = $structure->parameters[0]->value;//кодировка символов
 
            //0 - 7BIT; 1 - 8BIT; 2 - BINARY; 3 - BASE64; 4 - QUOTED-PRINTABLE; 5 - OTHER
            switch ($structure->encoding) {
                case 3:
                    //imap_base64 - декодирует BASE64-кодированный текст
                    $text = imap_base64($text);
                    break;
                case 4:
                    //imap_qprint - конвертирует закавыченную строку в 8-битную строку
                    $text = imap_qprint($text);
                    break;
            }
 
            if($mimetype == 'TEXT/PLAIN'){
                $text = iconv($charset,"UTF-8",$text);
            }
 
            if($mimetype == 'TEXT/HTML'){
                $text = iconv($charset,"UTF-8",$text);
            }
 
            return $text;
        }
    }
    return false;
}
 
//MIME-тип передается числом, а подтип - текстом.
//Функция приводит все в текстовый вид.
//Например: если type = 0 и subtype = "PLAIN",
//то функция вернет "TEXT/PLAIN".
//TEXT - 0, MULTIPART - 1, .. , APPLICATION - 3 и т.д.
public function getMimeType($structure) {
    $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", 
        "AUDIO", "IMAGE", "VIDEO", "OTHER");
    if ($structure->subtype) {
        return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
}
 
//перевести текст в дату MySQL
public function strToMysqlDate($text){
    $unixTimestamp=strtotime($text);
    return date("Y-m-d H:i:s", $unixTimestamp);
}
 
//заполняем ассоциативный массив, где ключом является тип адреса,
//а значение массив адресов
public function getAddress($header,$type,&$map){
    //проверка существования типа в заголовке
    if(property_exists($header,$type)){
        $arr = $header->$type;
        if(is_array($arr) && count($arr) > 0){
            $map[$type] = $arr;
        }
    }
}
 
//загрузка вложений
public function loadAttaches($mbox,$uid,$message_id){
    //получаем структуру сообщения
    $struct = imap_fetchstructure($mbox,$uid,FT_UID);
    $attachCount = 0;
    if(!$struct->parts) return $attachCount;
    //перебираем части сообщения
    foreach($struct->parts as $number => $part){
        //ищем части, у которых ifdisposition равно 1 и disposition равно ATTACHMENT,
        //все остальные части игнорируем. Также стоит заметить, что значение поля
        //disposition может быть как в верхнем, так и в нижнем регистрах,
        //т.е. может быть "attachment" и "ATTACHMENT". Поэтому в коде всё приведено
        //к верхнему регистру
        if(!$part->ifdisposition 
            || strtoupper($part->disposition) != "ATTACHMENT")continue;
        //получаем название файла
        $filename = getDecodedHeader($part->dparameters[0]->value);
        //получаем содержимое файла в закодированном виде
        $text = imap_fetchbody($mbox, $uid, $number + 1, FT_UID);
        //декодирование содержимого файла
        switch ($part->encoding) {
            case 3:
                $text = imap_base64($text);
                break;
            case 4:
                $text = imap_qprint($text);
                break;
        }
        //оригинальное название файла будем сохранять в базе данных.
        //Разные письма могут иметь вложения с одинаковыми названиями,
        //поэтому в файловой системе будем сохранять файла с уникальным именем,
        //сохранив при этом расширение файла
        $file_path = getStoreDirectory() . getUid() . getFileExtension($filename);
        file_put_contents($file_path,$text);
 
        $content_type = getMimeType($part);//MIME-тип файла
        $filesize = strlen($text);//размер файла
 
        //записываем информацию о файле в базу данных. Напомню, что в
        //базу сохраняется не сам файл, а относительный путь к файлу
        $sql = "INSERT INTO attachments(message_id,file_name,mime_type,
            file_size,location)" .
            "VALUES('$message_id',
            '" . mysql_real_escape_string($filename) . "',
            '" . mysql_real_escape_string($content_type) . "',
            $filesize,
            '" . mysql_real_escape_string($file_path) . "')";
        $res_ins = mysql_query($sql) or die(mysql_error());
        $attachCount++;
    }
    return $attachCount;
}

//генерация уникального идентификатора
public static function getUid(){
    if (function_exists('com_create_guid')){
        return str_replace("}", "", str_replace("{", "", com_create_guid()));
    } else {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid =
            substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
 
    }
    return strtolower($uuid);
}

}
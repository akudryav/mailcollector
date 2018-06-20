<?php

namespace app\models;

/**
 * Класс для парсинга сообщений gmail
 */
class MessageGMAIL extends Message
{

    //получение данных письма
    public function loadData()
    {

    }

    //раскодировка заголовка
    public static function getDecodedHeader($text)
    {

    }


    //заполняем ассоциативный массив, где ключом является тип адреса,
    //а значение массив адресов
    public function loadAddress()
    {
        
    }

    //загрузка вложений
    public function loadAttaches()
    {

    }


}
<?php

namespace app\models;

use Yii;
use yii\helpers\Html;

/**
 * This is the model class for table "message".
 *
 * @property int $id
 * @property int $mailbox_id
 * @property int $uid
 * @property string $from_ip
 * @property string $from_domain
 * @property string $subject
 * @property string $body_text
 * @property string $body_html
 * @property int $attachment_count
 * @property string $header
 * @property string $message_date
 * @property string $create_date
 * @property string $modify_date
 * @property int $is_ready
 *
 * @property Address[] $addresses
 * @property Attachment[] $attachments
 * @property Mailbox $mailbox
 */
class Message extends \yii\db\ActiveRecord
{
    private $mbox;
    public static $yes_no = ['Нет', 'Да'];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mailbox_id', 'uid'], 'required'],
            [['mailbox_id', 'uid', 'attachment_count', 'is_ready'], 'integer'],
            [['body_text', 'body_html', 'header'], 'string'],
            [['message_date', 'create_date', 'modify_date'], 'safe'],
            [['from_ip', 'from_domain', 'subject'], 'string', 'max' => 255],
            [['mailbox_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mailbox::className(), 'targetAttribute' => ['mailbox_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mailbox_id' => 'Почтовый Аккаунт',
            'uid' => 'Uid',
            'from_ip' => 'Ip отправителя',
            'from_domain' => 'Домен отправителя',
            'subject' => 'Тема',
            'body_text' => 'Текст',
            'body_html' => 'Html',
            'attachment_count' => 'Число вложений',
            'header' => 'Заголовки',
            'message_date' => 'Дата получения',
            'create_date' => 'Дата загрузки',
            'modify_date' => 'Дата изменения',
            'is_ready' => 'Загружено полностью',
        ];
    }
    
    public function statusName()
    {
        return isset(self::$yes_no[$this->is_ready]) ? self::$yes_no[$this->is_ready] : 'unknown';
    }
    // геттер-сеетер для imap потока письма
    public function setMbox($mail) 
    {
        $this->mbox = $mail;
    }
    public function getMbox() 
    {
        return $this->mbox;
    }
    
    //получение данных письма
    public function loadData()
    {
        //получение исходного заголовка письма
        $this->header = imap_fetchheader($this->mbox, $this->uid, FT_UID);
        // пытаемся найти ip отправителя
        $regex='/client\-ip\=(.+?)\;/s';
        if(preg_match($regex, $this->header, $matches)){
            $this->from_ip = $matches[1];
        }
        //получение заголовка письма в виде объекта
        $header = imap_rfc822_parse_headers($this->header);
        // хост отправителя
        $this->from_domain = $header->sender[0]->host;
        $this->subject = self::getDecodedHeader($header->subject);
        $this->message_date = self::strToMysqlDate($header->date);
        $this->body_text = $this->getTextBody();
        $this->body_html = $this->getHtmlBody();
        $this->modify_date = date("Y-m-d H:i:s");
        $this->is_ready = 1;
    }

    //раскодировка заголовка
    public static function getDecodedHeader($text){
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
    private function getTextBody()
    {
        return $this->getPart('TEXT/PLAIN');
    }

    //получение содержимого письма в виде формате html
    private function getHtmlBody()
    {
        return $this->getPart('TEXT/HTML');
    }

    /**
     * получение части письма
     * в зависимости от структуры письма
     */
    private function getPart($mimetype, $structure = false, $partNumber = false)
    {
        if (!$structure) {
            //получение структуры письма
            $structure = imap_fetchstructure($this->mbox, $this->uid, FT_UID);
        }
        if ($structure) {
            if ($mimetype == self::getMimeType($structure)) { // простое письмо
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $charset = $structure->parameters[0]->value;//кодировка символов
                //imap_fetchbody - извлекает определённый раздел тела сообщения
                $text = imap_fetchbody($this->mbox, $this->uid, $partNumber, FT_UID);
                //0 - 7BIT; 1 - 8BIT; 2 - BINARY; 3 - BASE64; 4 - QUOTED-PRINTABLE; 5 - OTHER
                switch ($structure->encoding) {
                    case 3:
                        //imap_base64 - декодирует BASE64-кодированный текст
                        $text = imap_base64($text);
                        break;
                    case 4:
                        //imap_qprint - конвертирует закавыченную печатаемую строку в 8-битную строку
                        $text = imap_qprint($text);
                        break;
                }
                // меняем кодировку на utf-8
                if($mimetype == 'TEXT/PLAIN' || $mimetype == 'TEXT/HTML'){
                    $text = iconv($charset, 'UTF-8', $text);
                }
                return $text;
            }

            // multipart
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = "";
                    if ($partNumber) {
                        $prefix = $partNumber . ".";
                    }
                    $data = $this->getPart($mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return null;
    }
   
    /**
     * MIME-тип передается числом, а подтип - текстом.
     * Функция приводит все в текстовый вид.
     * Например: если type = 0 и subtype = "PLAIN",
     * то функция вернет "TEXT/PLAIN".
     * TEXT - 0, MULTIPART - 1, .. , APPLICATION - 3 и т.д.
     * 
     * @param type $structure
     * @return string
     */
    private static function getMimeType($structure) 
    {
        $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
        if ($structure->subtype) {
            return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }

    //перевести текст в дату MySQL
    private static function strToMysqlDate($text)
    {
        $unixTimestamp=strtotime($text);
        return date("Y-m-d H:i:s", $unixTimestamp);
    }

    //заполняем ассоциативный массив, где ключом является тип адреса,
    //а значение массив адресов
    public function loadAddress()
    {
        //получение адресов из заголовка письма
        $address_map = [];
        //получение заголовка письма в виде объекта
        $header = imap_rfc822_parse_headers($this->header);
        foreach(['to','from','reply_to','sender','cc','bcc'] as $address_type){
            //проверка существования типа в заголовке
            if(property_exists($header, $address_type)){
                $arr = $header->$address_type;
                if(is_array($arr) && count($arr) > 0){
                    $address_map[$address_type] = $arr;
                }
            }
        }

        foreach($address_map as $key => $arr){
            foreach($arr as $obj){
                $type = $key;
                $address = "$obj->mailbox@$obj->host";//склеиваем email
                $model = new Address();
                $model->setAttributes([
                    'message_id' => $this->id,
                    'type' => $type,
                    'name' => self::getDecodedHeader($obj->personal),
                    'email' => $address,
                ]);
                if (!$model->save()) {
                    Yii::error('Error save address. ' . Html::errorSummary($model), 'mailer');
                }
            }
        }
        
    }

    //загрузка вложений
    public function loadAttaches()
    {
        //получаем структуру сообщения
        $struct = imap_fetchstructure($this->mbox, $this->uid,FT_UID);
        $attachCount = 0;
        if(!$struct->parts) return $attachCount;
        //перебираем части сообщения
        foreach($struct->parts as $number => $part){
            //ищем части, у которых ifdisposition равно 1 и disposition равно ATTACHMENT,
            //все остальные части игнорируем. Также стоит заметить, что значение поля
            //disposition может быть как в верхнем, так и в нижнем регистрах,
            //т.е. может быть "attachment" и "ATTACHMENT". Поэтому в коде всё приведено
            //к верхнему регистру
            if(!$part->ifdisposition || strtoupper($part->disposition) != "ATTACHMENT")continue;
            //получаем название файла
            $filename = self::getDecodedHeader($part->dparameters[0]->value);
            //получаем содержимое файла в закодированном виде
            $text = imap_fetchbody($this->mbox, $this->uid, $number + 1, FT_UID);
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
            $file_path = self::getStoreDirectory() . uniqid() . self::getFileExtension($filename);
            file_put_contents($file_path, $text);

            //записываем информацию о файле в базу данных. Напомню, что в
            //базу сохраняется не сам файл, а относительный путь к файлу
            $model = new Attachment();
            $model->setAttributes([
                'message_id' => $this->id,
                'file_name' => $filename,
                'mime_type' => self::getMimeType($part),
                'file_size' => strlen($text),
                'location' => $file_path,
            ]);
            if (!$model->save()) {
                Yii::error('Error save attachment. ' . Html::errorSummary($model), 'mailer');
            }
                
            $attachCount++;
        }
        $this->attachment_count = $attachCount;
        return $attachCount;
    }

    //Функция для получения пути к директории, где будут храниться файлы.
    //Файлы будут сохраняться в поддиректории, созданной по
    //текущей дате. Например, 2014-07-31. Это позволит
    //не держать файлы в одной директории. Много файлов в
    //одной директории замедляет чтение директории
    private static function getStoreDirectory()
    {
        $date_folder = Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR;
        if(!file_exists($date_folder)) mkdir($date_folder);
        return $date_folder;
    }

    //получаем расширение файла
    private static function getFileExtension($filename)
    {
        $arr = explode(".",$filename);
        return count($arr) > 1 ? "." . end($arr) : "";
    }
    
    public function showAttachments()
    {
        $list = [];
        foreach ($this->attachments as $r) {
            $list[] = Html::a($r->file_name, ['site/download', 'filename' => $r->location]);
        }
        return implode('<br>', $list);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Address::className(), ['message_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::className(), ['message_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMailbox()
    {
        return $this->hasOne(Mailbox::className(), ['id' => 'mailbox_id']);
    }
}
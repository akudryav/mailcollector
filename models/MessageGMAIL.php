<?php

namespace app\models;

use Yii;
use yii\helpers\Html;
use app\components\MailHelper;
/**
 * Класс для парсинга сообщений gmail
 */
class MessageGMAIL extends Message
{
    private $mbox;
    private $msgObj;

    // геттер-сеетер для imap потока письма
    public function setMbox($value)
    {
        $this->mbox = $value;
    }
    public function getMbox()
    {
        return $this->mbox;
    }

    public static function findByFullid($id)
    {
        return self::find()
            ->where(['full_id' => trim($id)])
            ->one();
    }

    //получение данных письма
    public function loadData()
    {
        $this->getMessage();
        $headerArr = $this->getHeaderArr();
        $bodyArr = $this->getBody();

        $headerString = implode('', $headerArr);
        // пытаемся найти ip отправителя
        $regex='/client\-ip\=(.+?)\;/s';
        if(preg_match($regex, $headerString, $matches)){
            $this->from_ip = $matches[1];
        }
        $regex='/from ([^\s])/s';
        if(preg_match($regex, self::getHeader($headerArr, 'Received'), $matches)){
            $this->from_domain = $matches[1];
        }
        $this->subject = self::getHeader($headerArr, 'Subject');
        $this->message_date = MailHelper::strToMysqlDate(self::getHeader($headerArr, 'Date'));
        $this->body_text = $bodyArr[0];
        $this->body_html = $bodyArr[1];
        $this->modify_date = date("Y-m-d H:i:s");
        $this->is_ready = 1;
        // загрузка адресов
        $this->loadAddress($headerArr);
    }


    //получение заголовка если есть
    private static function getHeader($array, $index)
    {
        return isset($array[$index]) ? $array[$index] : null;
    }


    //заполняем ассоциативный массив, где ключом является тип адреса,
    //а значение массив адресов
    public function loadAddress($headers)
    {
        foreach(['To','From','Reply_to','Sender','Cc','Bcc'] as $address_type){
            //проверка существования типа в заголовке
            if(isset($headers[$address_type])){
                $arr = self::segregateAddsress($headers[$address_type]);
                foreach($arr as $item) {
                    $model = new Address();
                    $model->setAttributes([
                        'message_id' => $this->id,
                        'type' => $address_type,
                        'name' => $item['name'],
                        'email' => $item['email'],
                    ]);
                    if (!$model->save()) {
                        Yii::error('Error save address. ' . Html::errorSummary($model), 'mailer');
                    }
                }
            }
        }
    }

    private static function segregateAddsress($in)
    {
        preg_match_all('!"(.*?)"\s+<\s*(.*?)\s*>!', $in, $matches);
        $out = array();
        for ($i=0; $i<count($matches[0]); $i++) {
            $out[] = array(
                'name' => $matches[1][$i],
                'email' => $matches[2][$i],
            );
        }
        return $out;
    }

    //загрузка вложений
    public function loadAttaches()
    {
        $attachCount = 0;
        $messageDetails = $this->msgObj->getPayload();
        foreach ($messageDetails['parts'] as $key => $value) {
            if (isset($value['body']['attachmentId'])) {
                $partId = $value['partId'];

                $attachmentDetails = [
                    'message_id' => $this->id,
                    'mime_type' => $messageDetails['parts'][$partId]['mimeType'],
                    'file_name' => $messageDetails['parts'][$partId]['filename'] ,
                    'file_size' => $messageDetails['parts'][$partId]['body']['size'],
                    'attachmentId' => $messageDetails['parts'][$partId]['body']['attachmentId']
                ];
                $attachment = $this->mbox->users_messages_attachments->get('me', $this->full_id, $attachmentDetails['attachmentId']);
                $attachmentDetails['data'] = self::base64url_decode($attachment->data);


                $file_path = MailHelper::getStoreDirectory() . uniqid() . MailHelper::getFileExtension($attachmentDetails['file_name']);
                file_put_contents($file_path, $attachmentDetails['data']);

                //записываем информацию о файле в базу данных. Напомню, что в
                //базу сохраняется не сам файл, а относительный путь к файлу
                $model = new Attachment();
                $model->setAttributes($attachmentDetails);
                $model->location = $file_path;
                if (!$model->save()) {
                    Yii::error('Error save attachment. ' . Html::errorSummary($model), 'mailer');
                }

                $attachCount++;
            }
        }
        $this->attachment_count = $attachCount;
        return $attachCount;
    }

    private function getMessage()
    {
        try {
            $this->msgObj = $this->mbox->users_messages->get('me', $this->full_id);
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    }

    private function getHeaderArr()
    {
        $outArr = [];
        foreach ($this->msgObj->getPayload()->getHeaders() as $key => $val) {
            $outArr[$val->name] = $val->value;
        }
        return $outArr;
    }

    private function getBody()
    {
        $outArr = [];
        foreach ($this->msgObj->getPayload()->getParts() as $key => $val) {
            $outArr[] = self::base64url_decode($val->getBody()->getData());
        }
        return $outArr;
    }

    private static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }


}
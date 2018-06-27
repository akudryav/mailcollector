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

        $this->header = implode('', $headerArr);
        $regex='/from ([^\s]+)/s';
        if(preg_match($regex, self::getElem($headerArr, 'Received'), $matches)){
            $this->from_domain = $matches[1];
        }

        $this->subject = self::getElem($headerArr, 'Subject');
        $this->message_date = MailHelper::strToMysqlDate(self::getElem($headerArr, 'Date'));
        $this->body_text = self::getElem($bodyArr, 0);
        $this->body_html = self::getElem($bodyArr, 1);
        $this->modify_date = date("Y-m-d H:i:s");
        $this->is_ready = 1;
        // загрузка адресов
        $this->loadAddress($headerArr);
    }

    //получение заголовка если есть
    private static function getElem($array, $index)
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
        foreach ($messageDetails['parts'] as $value) {
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
        foreach ($this->msgObj->getPayload()->getHeaders() as $val) {
            $outArr[$val->name] = $val->value;
        }
        return $outArr;
    }

    private function getBody()
    {
        $outArr = [];
        $payload = $this->msgObj->getPayload();
        // With no attachment, the payload might be directly in the body, encoded.
        $body = $payload->getBody();
        if($body && $payload['mimeType'] == 'text/plain') {
            $outArr[0] = self::base64url_decode($body->getData());
        }
        if($body && $payload['mimeType'] == 'text/html') {
            $outArr[1] = self::base64url_decode($body->getData());
        }

        // If we didn't find a body, let's look for the parts
        if(empty($outArr)) {
            $parts = $payload->getParts();
            foreach ($parts  as $part) {
                if(isset($part['body']) && $part['mimeType'] == 'text/plain') {
                    $outArr[0] = self::base64url_decode($part['body']->getData());
                }
                if(isset($part['body']) && $part['mimeType'] == 'text/html') {
                    $outArr[1] = self::base64url_decode($part['body']->getData());
                }
            }
        } if(empty($outArr)) {
            foreach ($parts  as $part) {
                // Last try: if we didn't find the body in the first parts, 
                // let's loop into the parts of the parts (as @Tholle suggested).
                if(isset($part['parts']) && !$FOUND_BODY) {
                    foreach ($part['parts'] as $p) {
                        // replace 'text/html' by 'text/plain' if you prefer
                        if(isset($p['body']) && $p['mimeType'] == 'text/plain') {
                            $outArr[0] = self::base64url_decode($part['body']->getData());
                        }
                        if(isset($p['body']) && $p['mimeType'] == 'text/html') {
                            $outArr[1] = self::base64url_decode($part['body']->getData());
                        }
                    }
                }
                if(!empty($outArr)) {
                    break;
                }
            }
        }
        
        return $outArr;
    }

    private static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }


}
<?php
namespace app\components;

use app\models\MessageIMAP;
use app\models\MessageGMAIL;

class MailHelper {

    public static function makeConnection($account)
    {
        if('gmail.com' == $account->server->host) {
            $cred = Token::findOne(['mailbox_id' => $account->id]);
            $client = $cred->getClient();
            return new GmailConnection($account);
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
}
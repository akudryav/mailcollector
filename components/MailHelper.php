<?php
namespace app\components;

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
}
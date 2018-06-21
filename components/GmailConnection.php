<?php

namespace app\components;

use Yii;
use app\models\Token;
use google\apiclient;

class GmailConnection extends \yii\base\Component {
    private $mailbox_id;
    private $email;
    private $client;
    private $credential;
    private $service;
    private $error;

    public function setMailbox_id($value)
    {
        $this->mailbox_id = $value;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function init()
    {
        $this->credential = Token::findOne(['mailbox_id' => $this->mailbox_id]);
        $this->client = $this->credential->getClient();
        if (!empty($this->credential->access_token)) {
            $accessToken = json_decode($this->credential->access_token, true);
        } else {
            echo 'Необходимо создать Oauth токен для аккаунта '.$this->email;
            return false;
        }
        $this->client->setAccessToken($accessToken);
        $this->refreshToken();
        $this->service = new \Google_Service_Gmail($this->client);
    }

    public function checkConnection()
    {
        return is_a ( $this->service , 'Google_Service_Gmail');
    }

    private function refreshToken()
    {
        // Refresh the token if it's expired.
        if ($this->client->isAccessTokenExpired()) {
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            $this->credential->access_token = json_encode($this->client->getAccessToken());
            $this->credential->save();
        }
    }
    

    public function disconnect()
    {

    }


    public function getMessages($range)
    {
        $messages = $this->service->users_messages->listUsersMessages('me');
        return $messages->getMessages();
    }

    public function getLastError()
    {
        return $this->error;
    }
}
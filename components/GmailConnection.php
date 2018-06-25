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

    public function getService()
    {
        return $this->service;
    }

    public function init()
    {
        $this->credential = Token::findOne(['mailbox_id' => $this->mailbox_id]);
        if(null == $this->credential || empty($this->credential->access_token)) {
            echo 'Необходимо создать Oauth токен для аккаунта '.$this->email.PHP_EOL;
            return false;
        }
        $this->client = $this->credential->getClient();
        $accessToken = json_decode($this->credential->access_token, true);
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

    public function getLastError()
    {
        return $this->error;
    }

    /**
     * Get list of Messages in user mailbox.
     *
     * @param  Google_Service_Gmail $service Authorized Gmail API instance.
     * can be used to indicate the authenticated user.
     * @return array Array of Messages.
     */
    public function getMessages($optArr = [])
    {
        $pageToken = NULL;
        $messages = [];
        do {
            try {
                if ($pageToken) {
                    $optArr['pageToken'] = $pageToken;
                }
                $messagesResponse = $this->service->users_messages->listUsersMessages('me', $optArr);
                if ($messagesResponse->getMessages()) {
                    $messages = array_merge($messages, $messagesResponse->getMessages());
                    $pageToken = $messagesResponse->getNextPageToken();
                }
            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
            }
        } while ($pageToken);

        return $messages;
    }

}
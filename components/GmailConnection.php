<?php

namespace app\components;

use Yii;
use app\models\Token;
use app\models\MessageGMAIL;
use google\apiclient;

class GmailConnection extends \yii\base\Component {
    private $account;
    private $client;
    private $credential;
    private $service;
    private $error;

    public function setAccount($value)
    {
        $this->account = $value;
    }

    public function getService()
    {
        return $this->service;
    }

    public function init()
    {
        $this->credential = Token::findOne(['mailbox_id' => $this->account->id]);
        if(null == $this->credential || empty($this->credential->access_token)) {
            echo 'Необходимо создать Oauth токен для аккаунта '.$this->account->email.PHP_EOL;
            return false;
        }
        $this->client = Token::getClient();
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
            // save refresh token to some variable
            $refreshTokenSaved = $this->client->getRefreshToken();
            $this->client->fetchAccessTokenWithRefreshToken($refreshTokenSaved);
            // pass access token to some variable
            $accessTokenUpdated = $this->client->getAccessToken();
            // append refresh token
            $accessTokenUpdated['refresh_token'] = $refreshTokenSaved;
            
            $this->credential->access_token = json_encode($accessTokenUpdated);
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

    public function readFolder($label = 'inbox')
    {
        //перебираем сообщения
        $optParams['labelIds'] = strtoupper($label);
        $optParams['q'] = 'after:'.date('Y/m/d', strtotime('-1 day', $this->account->check_time));

        $msg_count = 0;
        foreach ($this->getMessages($optParams) as $message) {
            try {
                $full_id = $message->getId();
                // Проверяем наличие сообщения в БД
                if(MessageGMAIL::findByFullid($full_id)) {
                    continue;
                }
                //отключаем Autocommit, будем сами управлять транзакциями
                $transaction = Yii::$app->db->beginTransaction();
                $model = new MessageGMAIL();

                //создаем запись в таблице messages,
                $model->setAttributes([
                    'mailbox_id' => $this->account->id,
                    'uid' => 1,
                    'full_id' => $full_id,
                    'label' => $label,
                    'create_date' => date("Y-m-d H:i:s"),
                    'is_ready' => 0
                ]);

                if (!$model->save()) {
                    Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                }

                Yii::info("loading message $label: $full_id", 'mailer');
                echo "loading message $label: $full_id" . PHP_EOL;
                $model->setMbox($this->getService());
                // загрузка данных
                $model->loadData();
                // загрузка вложений
                $model->loadAttaches();

                if (!$model->save()) {
                    Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                } else {
                    $msg_count ++;
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
            $transaction->commit();

        }
        return $msg_count;
    }

}
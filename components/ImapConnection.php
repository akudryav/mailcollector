<?php

namespace app\components;

use Yii;
use app\models\MessageIMAP;

class ImapConnection extends \yii\base\Component {
    private $account;
    private $config;
    private $imapStream = null;
    private $errors = [];
    // сеттеры
    public function setAccount($value)
    {
        $this->account = $value;
    }

    public function init()
    {
        // получаем данные почтового сервера
        $server = $this->account->server;
        //если подключение идет через SSL,
        //то достаточно добавить "/ssl" к строке подключения, и
        //поддержка SSL будет включена
        $ssl = $server->is_ssl ? "/ssl" : "";

        // конфигурация  подключения
        $this->config = [
            'imapPath' => "{{$server->imap}:{$server->port}{$ssl}}",
            'imapLogin' => $this->account->email,
            'imapPassword' => $this->account->password,
        ];
    }
    /**
     * Get IMAP mailbox connection stream
     * @param bool $forceConnection Initialize connection if it's not initialized
     * @return null|resource
     */
    public function getImapStream()
    {
        if(!$this->imapStream || !is_resource($this->imapStream)) {
            $this->imapStream = $this->initImapStream();
        }
        return $this->imapStream;
    }

    private function initImapStream()
    {

        $imap = @imap_open($this->config['imapPath'], $this->config['imapLogin'], $this->config['imapPassword']);
        $this->errors = imap_errors();

        return $imap;
    }

    public function disconnect()
    {
        if($this->imapStream && is_resource($this->imapStream)) {
            imap_close($this->imapStream, CL_EXPUNGE);
            $this->imapStream = null;
        }
    }

    public function checkConnection()
    {
        $imap = $this->getImapStream();
        if(!is_resource($imap)) return false;
        return imap_ping($imap);
    }

    public function getMessages($range)
    {
        $connection = $this->getImapStream();
        return imap_fetch_overview($connection, $range, FT_UID);
    }

    public function getMboxes()
    {
        $connection = $this->getImapStream();
        return imap_list($connection, $this->imapPath, '*');
    }

    public function getLastError()
    {
        return implode(',', $this->errors);
    }

    public function openSpam()
    {
        return imap_reopen($this->imapStream, $this->account->server->spam_folder);
    }

    public function readFolder($label = 'inbox')
    {
        $uid_from = $this->account->getMaxUid($label) + 1;
        $uid_to = 2147483647;
        $range = "$uid_from:$uid_to";
        $msg_count = 0;

        //перебираем сообщения
        foreach ($this->getMessages($range) as $message) {
            //получаем UID сообщения
            $message_uid = $message->uid;
            Yii::info("add message $message_uid", 'mailer');

            try {
                //отключаем Autocommit, будем сами управлять транзакциями
                $transaction = Yii::$app->db->beginTransaction();
                $model = new MessageIMAP();
                //создаем запись в таблице messages,
                $model->setAttributes([
                    'mailbox_id' => $this->account->id,
                    'uid' => $message_uid,
                    'label' => $label,
                    'create_date' => date("Y-m-d H:i:s"),
                    'is_ready' => 0
                ]);

                if (!$model->save()) {
                    Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                }

                Yii::info("loading message $label: $message_uid", 'mailer');
                echo "loading message $label: $message_uid" . PHP_EOL;
                $model->setMbox($this->getImapStream());
                // загрузка данных
                $model->loadData();
                // загрузка адресов
                $model->loadAddress();
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
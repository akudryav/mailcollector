<?php

namespace app\components;

use Yii;

class ImapConnection extends \yii\base\Component {
    private $imapPath;
    private $imapLogin;
    private $imapPassword;
    private $imapStream;
    // сеттеры
    public function setImapPath($value)
    {
        $this->imapPath = $value;
    }

    public function setImapLogin($value)
    {
        $this->imapLogin = $value;
    }

    public function setImapPassword($value)
    {
        $this->imapPassword = $value;
    }
    /**
     * Get IMAP mailbox connection stream
     * @param bool $forceConnection Initialize connection if it's not initialized
     * @return null|resource
     */
    public function getImapStream()
    {
        if($this->imapStream && (!is_resource($this->imapStream) || !imap_ping($this->imapStream))) {
            $this->disconnect();
            $this->imapStream = null;
        }
        if(!$this->imapStream) {
            $this->imapStream = $this->initImapStream();
        }
        return $this->imapStream;
    }

    private function initImapStream()
    {
        $imapStream = @imap_open($this->imapPath, $this->imapLogin, $this->imapPassword);
        if(!$imapStream) {
            echo 'Connection error: ' . imap_last_error();
        }
        return $imapStream;
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
        $connection = $this->getImapStream();
        return imap_ping($connection);
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

    public function getInfo()
    {
        $connection = $this->getImapStream();
        return imap_check($connection);
    }

    public function getLastError()
    {
        return imap_last_error();
    }
}
<?php

namespace app\components;

use Yii;

class GmailConnection extends \yii\base\Component {
    private $imapPath;
    private $imapLogin;
    private $imapPassword;
    private $imapStream;

    /**
     * Get IMAP mailbox connection stream
     * @param bool $forceConnection Initialize connection if it's not initialized
     * @return null|resource
     */
    protected function getImapStream()
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

    protected function initImapStream()
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
        $mail = $this->getImapStream();
        return imap_ping($mail);
    }

    public function getMessages($range)
    {
        $mail = $this->getImapStream();
        return imap_fetch_overview($mail, $range, FT_UID);
    }
}
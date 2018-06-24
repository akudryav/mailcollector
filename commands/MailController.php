<?php

namespace app\commands;

use app\models\MessageGMAIL;
use app\models\MessageIMAP;
use Yii;
use yii\helpers\Html;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Mailbox;
use app\models\Message;
use app\components\MailHelper;

/**
 *предполагается, что данный скрипт будет запускаться планировщиком
 *через равные промежутки времени. Например, каждые 30 секунд.
 *Но может возникнуть ситуация, что предыдущий может не успеть завершить
 *свою работу. Для решения этой проблемы, когда скрипт запускается он
 *накладывает блокировку на заданный нами файл, совершив свою работу
 *скрипт снимает блокировку с файла и удаляет его. В это время, если по
 *расписанию сработал еще один запуск скрипта, то он увидит блокировку и
 *завершиться ничего не делая
 *reader.lock - файл, который мы будем использовать для блокировки.
 *Файл блокировки будем храниться в директории runtime
 */
class MailController extends Controller
{
    public $lock = "reader.lock";

    /**
     * Проверка блокировки файла
     * логическая переменная $aborted будет сигнализировать о том, что
     * предыдущий скрипт был прерван. Для этого проверяем существование
     * файла, а затем пытается получить время последней модификации файла
     * Если файл блокировки существует и удалось получить время последнего
     * изменения файла, то значит предыдущий скрипт был прерван
     */
    private function makeLock($prefix=null)
    {
        $lock_path = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $prefix. $this->lock;
        $aborted = file_exists($lock_path) ? filemtime($lock_path) : false;
        if ($aborted) { //если выполнение предыдущего скрипта было прервано
            Yii::info('Выполнение предыдущего скрипта было прервано', 'mailer'); //пишем в лог, что прервано
        }
        $fp = fopen($lock_path, 'w'); //открывает файл с возможностью записи
        if (!flock($fp, LOCK_EX | LOCK_NB)) { //если не удалось наложить блокировку, то значит предыдущий скрипт еще работает.
            Yii::info('Предыдущий скрипт еще работает', 'mailer'); //пишем в лог, что занято
            return false;
        }
        Yii::info('Начинаем проверять почту', 'mailer');
        return $fp;
    }

    private function releaseLock($fp)
    {
        Yii::info('Закончили', 'mailer');
        flock($fp, LOCK_UN);//снимаем блокировку с файла
        $meta_data = stream_get_meta_data($fp);
        $filename = $meta_data["uri"];
        fclose($fp);//закрываем файл
        unlink($filename);//удаляем файл
    }
    
    private function connectImap($account)
    {

    }

    /**
     * Скачивание писем (полная загрузка) по IMAP
     * Считываем только письма, у которых UID больше,
    чем UID последнего считанного сообщения. Для этого воспользуемся
    функцией imap_fetch_overview, у которой первый параметр - IMAP поток,
    второй параметр - диапазон номеров и третий параметр - константа FT_UID.
    FT_UID говорит о том, что диапазоны задаются UID-ами, иначе порядковыми
    номерами сообщений. Здесь важно понять разницу.
    Порядковый номер письма показывает номер писема среди писем почтового ящика,
    но если кол-во писем уменьшить, то порядковый номер может измениться.
    UID письма - это уникальный номер письма, также присваивается по порядку,
    но не изменяется.

    Сейчас и в дальнейшем мы же будем полагаться только на UID писем.
    Диапазаны можно задать следующим образом:
    "2,4:6" - что соответствует UID-ам 2,4,5,6
    "7:10" - соответствует 7,8,9,10
    В нашем случае для удобста будем брать диапазон от последнего UID + 1
    и до 2147483647.
     */
    public function actionLoadImap()
    {
        $fp = $this->makeLock('imap');
        if (!$fp) return ExitCode::CANTCREAT;

        foreach (Mailbox::getImap() as $account) {
            $mail = MailHelper::makeConnection($account);
            echo "Read {$account->email}";
            
            if (!$mail || !$mail->checkConnection()) {
                //пишем влог сообщение о неудачной попытке подключения
                Yii::error('Error opening Connection. ' . $mail->getLastError(), 'mailer');
                continue;//переходим к следующему ящику
            }

            $uid_from = $account->last_message_uid + 1;
            $uid_to = 2147483647;
            $range = "$uid_from:$uid_to";
            $message_uid = -1;
            $msg_count = 0;
            // var_dump($mail->getMboxes());
            //перебираем сообщения
            foreach ($mail->getMessages($range) as $message) {
                //получаем UID сообщения
                $message_uid = $message->uid;
                Yii::info("add message $message_uid", 'mailer');

                try {
                    //отключаем Autocommit, будем сами управлять транзакциями
                    $transaction = Mailbox::getDb()->beginTransaction();
                    $model = new MessageIMAP();
                    //создаем запись в таблице messages,
                    $model->setAttributes([
                        'mailbox_id' => $account->id,
                        'uid' => $message_uid,
                        'create_date' => date("Y-m-d H:i:s"),
                        'is_ready' => 0
                    ]);

                    if (!$model->save()) {
                        Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                    }

                    Yii::info("loading message $message_uid", 'mailer');
                    echo "loading message $message_uid" . PHP_EOL;
                    $model->setMbox($mail->getImapStream());
                    // загрузка данных
                    $model->loadData();
                    // определяем язык
                    $model->detectLang();
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
            if ($message_uid != -1) {
                Yii::info("last message uid = $message_uid", 'mailer');

                //если появились новые сообщения,
                //то сохраняем UID последнего сообщения
                $account->last_message_uid = $message_uid;
                $account->check_time = time();
                $account->save();
            } else {
                //нет новых сообщений
                Yii::info('no new messages', 'mailer');
            }
            echo 'Loaded messages: '.$msg_count. PHP_EOL;
            //закрываем поток
            $mail->disconnect();
        }

        // удаляем блокировку
        $this->releaseLock($fp);
        return ExitCode::OK;


    }
     /**
     * Скачивание писем (полная загрузка) по GMAIL
     */
    public function actionLoadGmail()
    {
        $fp = $this->makeLock('gmail');
        if (!$fp) return ExitCode::CANTCREAT;

        foreach (Mailbox::getGmail() as $account) {
            $mail = MailHelper::makeConnection($account);
            echo "Read {$account->email}";

            if (!$mail || !$mail->checkConnection()) {
                //пишем влог сообщение о неудачной попытке подключения
                Yii::error('Error opening Connection. ' . $mail->getLastError(), 'mailer');
                continue;//переходим к следующему ящику
            }
            $msg_count = 0;
            //перебираем сообщения
            $optParams['labelIds'] = 'INBOX';
            foreach ($mail->getMessages($optParams) as $message) {
                try {
                    $full_id = $message->getId();
                    //отключаем Autocommit, будем сами управлять транзакциями
                    //$transaction = Mailbox::getDb()->beginTransaction();
                    $model = new MessageGMAIL();
                    // Проверяем наличие сообщения в БД
                    if($model->findByFullid($full_id)) {
                        continue;
                    }
                    //создаем запись в таблице messages,
                    $model->setAttributes([
                        'mailbox_id' => $account->id,
                        'uid' => 1,
                        'full_id' => $full_id,
                        'create_date' => date("Y-m-d H:i:s"),
                        'is_ready' => 0
                    ]);

                    if (!$model->save()) {
                        Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                    }

                    Yii::info("loading message $full_id", 'mailer');
                    echo "loading message $full_id" . PHP_EOL;
                    $model->setMbox($mail->getService());
                    // загрузка данных
                    $model->loadData();
                    // определяем язык
                    $model->detectLang();
                    // загрузка вложений
                    $model->loadAttaches();

                    if (!$model->save()) {
                        Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                    } else {
                        $msg_count ++;
                    }

                } catch (\Exception $e) {
                    //$transaction->rollBack();
                    throw $e;
                } catch (\Throwable $e) {
                    //$transaction->rollBack();
                    throw $e;
                }
                //$transaction->commit();

            }
            if ($msg_count > 0) {
                $account->check_time = time();
                $account->save();
            } else {
                //нет новых сообщений
                Yii::info('no new messages', 'mailer');
            }
            echo 'Loaded messages: '.$msg_count. PHP_EOL;
            //закрываем поток
            $mail->disconnect();
        }

        // удаляем блокировку
        $this->releaseLock($fp);
        return ExitCode::OK;
    }

}

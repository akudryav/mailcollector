<?php

namespace app\commands;

use Yii;
use yii\helpers\Html;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Mailbox;
use app\models\Message;

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
    private function makeLock()
    {
        $lock_path = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $this->lock;
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
        fclose($fp);//закрываем файл
        unlink(Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . $this->lock);//удаляем файл
    }

    /**
     * чтение новых писем (без полной загрузки)
     */
    public function actionRead()
    {
        $fp = $this->makeLock();
        if (!$fp) return ExitCode::CANTCREAT;

        foreach (Mailbox::find()->where(['is_deleted' => 0])->all() as $account) {
            //если подключение идет через SSL, 
            //то достаточно добавить "/ssl" к строке подключения, и
            //поддержка SSL будет включена
            $ssl = $account->is_ssl ? "/ssl" : "";

            //строка подключения
            $conn = "{{$account->host}:{$account->port}{$ssl}}";
            Yii::info("Read {$account->email}, conn = $conn", 'mailer');
            echo "Read {$account->email}, conn = $conn" . PHP_EOL;

            //открываем IMAP-поток
            $mail = imap_open($conn, $account->email, $account->password);
            if (!$mail) {
                //пишем влог сообщение о неудачной попытке подключения
                Yii::error('Error opening IMAP. ' . imap_last_error(), 'mailer');
                continue;//переходим к следующему ящику
            }
            try {
                //отключаем Autocommit, будем сами управлять транзакциями
                $transaction = Mailbox::getDb()->beginTransaction();
                
                /*
                Считываем только письма, у которых UID больше,
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
                $uid_from = $account->last_message_uid + 1;
                $uid_to = 2147483647;
                $range = "$uid_from:$uid_to";
                $arr = imap_fetch_overview($mail, $range, FT_UID);
                $message_uid = -1;
                //перебираем сообщения
                foreach ($arr as $obj) {
                    //получаем UID сообщения
                    $message_uid = $obj->uid;
                    Yii::info("add message $message_uid", 'mailer');

                    //создаем запись в таблице messages,
                    //тем самым поставив сообщение в очередь на загрузку
                    $model = new Message();
                    $model->setAttributes([
                        'mailbox_id' => $account->id,
                        'uid' => $message_uid,
                        'create_date' => date("Y-m-d H:i:s"),
                        'is_ready' => 0
                    ]);
                    if (!$model->save()) {
                        Yii::error('Error save message. ' . Html::errorSummary($model), 'mailer');
                    }
                }
                if ($message_uid != -1) {
                    Yii::info("last message uid = $message_uid", 'mailer');

                    //если появились новые сообщения, 
                    //то сохраняем UID последнего сообщения
                    $account->last_message_uid = $message_uid;
                    $account->save();
                } else {
                    //нет новых сообщений
                    Yii::info('no new messages', 'mailer');
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
            $transaction->commit();
            //закрываем IMAP-поток
            imap_close($mail);
        }

        // удаляем блокировку
        $this->releaseLock($fp);
        return ExitCode::OK;


    }
     /**
     * Скачивание писем (полная загрузка)
     */
    public function actionLoad()
    {
        //составим список только тех почтовых ящиков,
        //сообщения которых еще не скачаны
        $ids = Mailbox::getUnloaded();
        $fp = $this->makeLock();
        if (!$fp) return ExitCode::CANTCREAT;

        foreach (Mailbox::findAll($ids) as $account) {
            $ssl = $account->is_ssl ? "/ssl" : "";
            //строка подключения
            $conn = "{{$account->host}:{$account->port}{$ssl}}";
            Yii::info("Read {$account->email}, conn = $conn", 'mailer');
            echo "Read {$account->email}, conn = $conn" . PHP_EOL;

            //открываем IMAP-поток
            $mail = imap_open($conn, $account->email, $account->password);
            if (!$mail) {
                //пишем влог сообщение о неудачной попытке подключения
                Yii::error('Error opening IMAP. ' . imap_last_error(), 'mailer');
                continue;//переходим к следующему ящику
            }
            //получаем список сообщений, которые необходимо скачать с почтового ящика
            foreach ($account->getMessages()->where(['is_ready' => 0])->all() as $message){
                try {
                    //отключаем Autocommit, будем сами управлять транзакциями
                    $transaction = Mailbox::getDb()->beginTransaction();
                    Yii::info("load message {$message->uid}", 'mailer');
                    echo "load message {$message->uid}" . PHP_EOL;
                    $message->setMbox($mail);
                    // загрузка данных
                    $message->loadData();
                    // загрузка адресов
                    $message->loadAddress();
                    // загрузка вложений
                    $message->loadAttaches();
                    if (!$message->save()) {
                        Yii::error('Error save message. ' . Html::errorSummary($message), 'mailer');
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
            //закрываем IMAP-поток
            imap_close($mail);
        }

        // удаляем блокировку
        $this->releaseLock($fp);
        return ExitCode::OK;
    }

}

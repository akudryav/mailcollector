<?php

namespace app\commands;

use Yii;
use app\components\ImapConnection;
use app\components\GmailConnection;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Mailbox;
use app\models\User;

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
            $mail = new ImapConnection(['account' => $account]);
            echo "Read {$account->email}".PHP_EOL;
            
            if (!$mail || !$mail->checkConnection()) {
                //пишем влог сообщение о неудачной попытке подключения
                $err = $mail->getLastError();
                echo $err;
                Yii::error('Error opening Connection. ' . $err, 'mailer');
                continue;//переходим к следующему ящику
            }
            // получаем из папки INBOX по умолчанию
            $msg_count = $mail->readFolder();
            Yii::info('Получено писем INBOX: ' . $msg_count, 'mailer');
            // переключаем на Спам и снова получаем
            if(!$mail->openSpam()) {
                Yii::error('Error spam Connection. ' . $mail->getLastError(), 'mailer');
                continue;//переходим к следующему ящику
            }
            $msg_count = $mail->readFolder('spam');
            Yii::info('Получено писем SPAM: ' . $msg_count, 'mailer');

            $account->check_time = time();
            $account->save();

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
            $mail = new GmailConnection(['account' => $account]);
            echo "Read {$account->email}".PHP_EOL;

            if (!$mail || !$mail->checkConnection()) {
                //пишем влог сообщение о неудачной попытке подключения
                Yii::error('Error opening Connection. ' . $mail->getLastError(), 'mailer');
                continue;//переходим к следующему ящику
            }
            $msg_count = $mail->readFolder();
            Yii::info('Получено писем INBOX: ' . $msg_count, 'mailer');
            $msg_count = $mail->readFolder('spam');
            Yii::info('Получено писем SPAM: ' . $msg_count, 'mailer');

            $account->check_time = time();
            $account->save();

            //закрываем поток
            $mail->disconnect();
        }

        // удаляем блокировку
        $this->releaseLock($fp);
        return ExitCode::OK;
    }

    public function actionAddAdmin() {
        echo 'Добавление нового пользователя-админа.'.PHP_EOL;
        $stdin = fopen('php://stdin', 'r');
        $yes = false;
        // вводим логин
        while (!$yes) {
            echo 'Введите логин:'.PHP_EOL;
            $login = trim(fgets($stdin));

            $model = User::find()->where(['username' => $login])->one();
            if (empty($model)) {
                $yes = true;
            } else {
                echo 'Этот логин занят!';
            }
        }
        // вводим email
        $yes = false;
        while (!$yes) {
            echo 'Введите email:'.PHP_EOL;
            $email = trim(fgets($stdin));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $model = User::find()->where(['email' => $email])->one();
                if (empty($model)) {
                    $yes = true;
                } else {
                    echo 'Этот email занят!';
                }
            } else {
                echo "E-mail адрес '$email' некорректный.\n";
            }

        }
        // Вводим пароль
        echo 'Введите пароль:'.PHP_EOL;
        $password = trim(fgets($stdin));

        $user = new User();
        $user->username = $login;
        $user->email = $email;
        $user->status = User::STATUS_ADMIN;
        $user->setPassword($password);
        $user->generateAuthKey();
        if ($user->save()) {
            echo 'Новый админ '.$login.' добавлен!';
        } else {
            var_dump($user->errors);
        }

    }


}

<?php

namespace app\controllers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Mailbox;
use app\models\Token;
use app\models\MailboxSearch;
use app\models\CsvUploadForm;
use yii\web\UploadedFile;
use app\components\AdminController;
use yii\web\NotFoundHttpException;
use google\apiclient;
set_include_path(Yii::$app->BasePath  . '/vendor/google/apiclient/src');

/**
 * MailboxController implements the CRUD actions for Mailbox model.
 */
class MailboxController extends AdminController
{

    /**
     * Lists all Mailbox models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new MailboxSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'csv' => new CsvUploadForm(),
            'json' => new Token(),
        ]);
    }

    public function actionCallback($code)
    {
        if (!Yii::$app->session->has('mailbox_id')) return false;
        $client = Token::getClient();
        $token = new Token();
        $token->mailbox_id = Yii::$app->session->get('mailbox_id');

        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
        $token->access_token = json_encode($accessToken);
        if($token->save()) {
            Yii::$app->getSession()->setFlash('info', 'Oauth Токен создан');
        }
        return $this->redirect(['index']);
    }

    public function actionToken($id)
    {
        $client = Token::getClient();
        // Request authorization from the user.
        Yii::$app->session->set('mailbox_id', $id);
        $url = Yii::$app->urlManager->createAbsoluteUrl('mailbox/callback');
        $client->setRedirectUri($url);
        // переходим по ссылке авторизации
        return $this->redirect($client->createAuthUrl());
    }

    /**
    * Загрузка данных из  csv
    */
    public function actionImport()
    {
        $model = new CsvUploadForm();
        if (Yii::$app->request->isPost) {
            $model->csvFile = UploadedFile::getInstance($model, 'csvFile');
            if ($model->upload()) {
                $delimeter = $model->detectDelimiter();
                $handle = fopen($model->path, 'r');
                // счетчики 
                $row = 1; $inserted = 0; $updated = 0; $error = 0;
                while (($data = fgetcsv($handle, 1000, $delimeter)) !== FALSE) {
                    $is_new = false;
                    if (!filter_var($data[0], FILTER_VALIDATE_EMAIL)) { // пропускаем первую строку, если первым не email
                        continue;
                    }
                    $boxmodel = Mailbox::findByMail($data[0]);
                    // если нет создаем новую запись
                    if(null == $boxmodel){
                        $boxmodel=new Mailbox();
                        $boxmodel->email=trim($data[0]);
                        $is_new = true;
                    }
                   
                    $boxmodel->password=trim($data[1]);
                    $boxmodel->backup_email = isset($data[2]) ? trim($data[2]) : null;              
                    $boxmodel->phone = isset($data[3]) ? trim($data[3]) : null;
                    $boxmodel->buyer = isset($data[4]) ? trim($data[4]) : null;
                    // при указании вертикали
                    if(isset($data[5])) {
                        $boxmodel->vertical_id = trim($data[5]);
                    } else {
                        $boxmodel->vertical_id = trim(Yii::$app->request->post('CsvUploadForm')['vertical']);
                    }
                    
                    if(!$boxmodel->save()){
                        Yii::$app->getSession()->setFlash('warning', 'Строка '.$row. Html::errorSummary($boxmodel));
                        $error ++;
                    } else {
                        if($is_new) $inserted ++; else $updated ++;
                    }
                    $row ++;
                }
                Yii::$app->getSession()->setFlash('info', 'Данные импортированы. Добавлено: '.
                        $inserted. ' Обновлено: '.$updated. ' Ошибок: '.$error);
                fclose($handle);
                unlink ($model->path);
            } else {
                Yii::$app->getSession()->setFlash('error', Html::errorSummary($model));
            }
        }
        return $this->redirect(['index']);
    }
    /**
     * Displays a single Mailbox model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Mailbox model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Mailbox();
        $model->is_deleted = 0;
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->getSession()->setFlash('success', 'Данные успешно сохранены');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Mailbox model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->getSession()->setFlash('success', 'Данные успешно сохранены');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'token' => Token::findOne(['mailbox_id' => $model->id]),
        ]);
    }

    /**
     * Deletes an existing Mailbox model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Mailbox model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Mailbox the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Mailbox::findOne(['id' => $id, 'user_id' => Yii::$app->user->identity->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

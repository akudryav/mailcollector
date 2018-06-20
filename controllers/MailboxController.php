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

    public function actionToken($id)
    {
        $request = Yii::$app->request;
        $cred = Token::findOne(['mailbox_id' => $id]);
        $client = $cred->getClient();
        $accessToken = false;
        $data = [];
        // если получаем через форму
        if ($request->isPost) {
            $authCode = $request->post('authCode');
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $cred->access_token = json_encode($accessToken);
            if($cred->save()) {
                Yii::$app->getSession()->setFlash('info', 'Oauth Токен создан');
            }
        } else {
            if (!empty($cred->access_token)) {
                $accessToken = json_decode($cred->access_token, true);
            } else {
                // Request authorization from the user.
                $data['authUrl'] = $client->createAuthUrl();
            }
        }
                
        if($accessToken) {
            $client->setAccessToken($accessToken);
            // Refresh the token if it's expired.
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $cred->access_token = json_encode($client->getAccessToken());
                $cred->save();
                Yii::$app->getSession()->setFlash('info', 'Oauth Токен обновлен');
            }
        }
        
        return $this->render('token', $data);
    }
    /**
     * Загрузка json
     */
    public function actionCredential($id)
    {
        $token = Token::findOne(['mailbox_id' => $id]);
        if ($token == null) {
            $token = new Token();
            $token->mailbox_id = $id;
        } else {
            $oldfile = Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR . $token->secret_file;
        }

        if (Yii::$app->request->isPost) {
            // загружаем файл
            $token->secret_file = UploadedFile::getInstance($token, 'secret_file');
            if ($token->upload()) {
                Yii::$app->getSession()->setFlash('info', 'Загружен '.$token->secret_file->name);
                if($token->save() && isset($oldfile) && basename($oldfile) != $token->secret_file  && is_file($oldfile)) {
                    // удаляем старый файл
                    unlink($oldfile);
                }
            }
        }

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
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
                    if (!filter_var($data[0], FILTER_VALIDATE_EMAIL)) { // пропускаем строку, если первым не email
                        continue;
                    }
                    $boxmodel = Mailbox::findByMail($data[0]);
                    // если нет создаем новую запись
                    if(null == $boxmodel){
                        $boxmodel=new Mailbox();
                        $boxmodel->email=$data[0];
                        $is_new = true;
                    } 
                   
                    $boxmodel->password=$data[1];
                    $boxmodel->buyer = isset($data[2]) ? $data[2] : null;
                    $boxmodel->phone = isset($data[3]) ? $data[3] : null;
                    // при указании вертикали
                    $boxmodel->vertical_id = Yii::$app->request->post('CsvUploadForm')['vertical'];
                    
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
        if (($model = Mailbox::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

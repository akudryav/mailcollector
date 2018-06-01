<?php

namespace app\controllers;

use Yii;
use yii\helpers\Html;
use app\models\Mailbox;
use app\models\MailboxSearch;
use app\models\UploadForm;
use yii\web\UploadedFile;
use app\components\AdminController;
use yii\web\NotFoundHttpException;

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
            'csv' => new UploadForm(),
        ]);
    }
    /**
    * Загрузка данных из  csv
    */
    public function actionImport()
    {
        $model = new UploadForm();
        if (Yii::$app->request->isPost) {
            $model->csvFile = UploadedFile::getInstance($model, 'csvFile');
            if ($file = $model->upload()) {
                // file is uploaded successfully
                $handle = fopen($file, 'r');
                // счетчики 
                $row = 1; $inserted = 0; $updated = 0; $error = 0;
                while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
                    $row ++;
                    $is_new = false;
                    if(1 == $row) { // пропускаем первую строку заголовков
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
                    $boxmodel->buyer = $data[2];
                    $boxmodel->phone = $data[3];
                    if(!$boxmodel->save()){
                        Yii::$app->getSession()->setFlash('warning', 'Строка '.($row-1). Html::errorSummary($boxmodel));
                        $error ++;
                    } else {
                        if($is_new) $inserted ++; else $updated ++;
                    }
                }
                Yii::$app->getSession()->setFlash('info', 'Данные импортированы. Добавлено: '.
                        $inserted. ' Обновлено: '.$updated. ' Ошибок: '.$error);
                fclose($handle);
                unlink ($file);
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

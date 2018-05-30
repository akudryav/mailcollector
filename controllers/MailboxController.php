<?php

namespace app\controllers;

use Yii;
use yii\helpers\Html;
use app\models\Mailbox;
use app\models\UploadForm;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
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
        $dataProvider = new ActiveDataProvider([
            'query' => Mailbox::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
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

                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $boxmodel=new Mailbox();
                    $boxmodel->email=$data[0];
                    $boxmodel->password=$data[1];
                    $boxmodel->host = $data[2];
                    $boxmodel->port = $data[3];
                    $boxmodel->is_ssl = $data[4];
                    if(!$boxmodel->save()){
                        Yii::$app->getSession()->setFlash('warning', Html::errorSummary($boxmodel));
                    }
                }
                Yii::$app->getSession()->setFlash('success', 'Данные успешно импортированы');
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
        $model->port = '993';
        $model->is_ssl = 1;
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

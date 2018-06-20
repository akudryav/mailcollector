<?php

namespace app\controllers;

use Yii;
use app\models\Token;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;
use app\components\AdminController;
use yii\web\NotFoundHttpException;

/**
 * TokenController implements the CRUD actions for Token model.
 */
class TokenController extends AdminController
{

    /**
     * Lists all Token models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Token::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Token model.
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
     * Creates a new Token model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Token();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->secret_file = UploadedFile::getInstance($model, 'secret_file');

            if ($model->upload() && $model->save()) {
                Yii::$app->getSession()->setFlash('success', 'Данные успешно сохранены');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Token model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        if ($request->isPost) {
            $oldfile = Yii::getAlias('@attachments') . DIRECTORY_SEPARATOR . $model->secret_file;
            $newfile = UploadedFile::getInstance($model, 'secret_file');
            if(!empty($newfile)) {
                $model->secret_file = $newfile;
                $model->upload();
                if(basename($oldfile) != $newfile->name  && is_file($oldfile)){
                    // удаляем старый файл
                    unlink($oldfile);
                }
            }

            if ($model->save()) {
                Yii::$app->getSession()->setFlash('success', 'Данные успешно сохранены');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Token model.
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
     * Finds the Token model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Token the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Token::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

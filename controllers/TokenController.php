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
        $query = Token::find();

        if(!Yii::$app->user->identity->isAdmin()) {
            $query->joinWith('mailbox')->where(['mailbox.user_id' => Yii::$app->user->identity->id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
        $query = Token::find()->joinWith('mailbox')->where(['token.id' => $id]);

        if(!Yii::$app->user->identity->isAdmin()) {
            $query->andWhere(['mailbox.user_id' => Yii::$app->user->identity->id]);
        }

        $model = $query->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

<?php

namespace app\components;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class AdminController extends Controller {

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

}
<?php

use yii\helpers\Html;
use yii\grid\GridView;
use  yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Токены';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="token-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить токен', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [

            'id',
            'mailbox_id',
            [
                'attribute' => 'access_token',
                'value' => function ($model) {
                    return StringHelper::truncate($model->access_token, 50);
                },
            ],
            'secret_file',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>

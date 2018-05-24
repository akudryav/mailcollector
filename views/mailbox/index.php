<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Аккаунты';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mailbox-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить Аккаунт', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'email:email',
            'password',
            'host',
            'port',
            [
                'attribute'=>'is_deleted',
                'value' => function($model) {
                    return $model->statusName();
                }
            ],
            'last_message_uid',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>

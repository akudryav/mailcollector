<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Messages';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="message-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Message', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'mailbox',
                'value' => 'mailbox.email'
            ],
            'uid',
            //'from_ip',
            //'from_domain',
            'subject',
            //'body_text:ntext',
            //'body_html:ntext',
            'attachment_count',
            //'header:ntext',
            'message_date',
            'create_date',
            //'modify_date',
            [
                'attribute'=>'is_ready',
                'value' => function($model) {
                    return $model->statusName();
                }
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>

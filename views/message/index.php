<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Письма';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="message-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'mailbox',
                'value' => 'mailbox.email'
            ],
            'label',
            'mailer',
            'ip_type',
            'language',
            //'from_ip',
            //'from_domain',
            'subject',
            //'body_text:ntext',
            //'body_html:ntext',
            'attachment_count',
            //'header:ntext',
            'message_date',


            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Действия',
                'headerOptions' => ['width' => '100'],
            ],
        ],
    ]); ?>
</div>

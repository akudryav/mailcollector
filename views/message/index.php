<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use app\models\Server;
use app\models\Vertical;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Письма';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="message-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            [
                'header'=>'Аккаунт',
                'attribute' => 'email',
                'value' => 'mailbox.email'
            ],
            [
                'header'=>'Провайдер',
                'attribute' => 'server',
                'value' => 'server.host',
                'filter'=>ArrayHelper::map(Server::find()->asArray()->all(), 'id', 'host'),
            ],
            [
                'header'=>'Вертикаль',
                'attribute' => 'vertical',
                'value' => 'vertical.name',
                'filter'=>ArrayHelper::map(Vertical::userList()->asArray()->all(), 'id', 'name'),
            ],
            'label',
            'mailer',
            'from_domain',
            'ip_type',
            'language',
            'subject',
            'message_date:datetime',


            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Действия',
                'headerOptions' => ['width' => '120'],
            ],
        ],
    ]); ?>
</div>

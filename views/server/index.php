<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Провайдеры';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="server-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить провайдера', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [

            'id',
            'host',
            'imap',
            'port',
            [
                'attribute'=>'is_ssl',
                'value' => function($model) {
                    return app\components\MailHelper::yesOrNo($model->is_ssl);
                }
            ],
            'spam_folder',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>

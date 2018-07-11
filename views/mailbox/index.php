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
        <?= Html::a('Импорт из CSV', '#', ['id' => 'csv_button', 'class' => 'btn btn-primary']) ?>
    </p>
    
    <?php echo $this->render('_csvform', ['model' => $csv]);?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'email:email',
            'backup_email:email',
            'buyer',
            'phone',
            [
                'attribute'=>'is_deleted',
                'value' => function($model) {
                    return app\components\MailHelper::yesOrNo($model->is_deleted);
                }
            ],
            'vertical.name',
            'check_time:datetime',
            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Действия',
                'headerOptions' => ['width' => '130'],
                'template' => '{token} {view} {update} {delete}',
                'buttons'=>
                    [
                        'token' => function ($url, $model, $key) {
                            return $model->tokenUrl();
                        }
                    ],
            ]
        ],
    ]); ?>

</div>

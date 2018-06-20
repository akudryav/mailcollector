<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap\Modal;
use yii\widgets\ActiveForm;

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
            'buyer',
            'phone',
            [
                'attribute'=>'is_deleted',
                'value' => function($model) {
                    return $model->statusName();
                }
            ],
            'vertical.name',
            'last_message_uid',

            [
                'class' => 'yii\grid\ActionColumn',
                'header'=>'Действия',
                'headerOptions' => ['width' => '130'],
                'template' => '{credential} {view} {update} {delete}',
                'buttons'=>
                    [
                        'credential' => function ($url, $model, $key) {
                            return $model->needCredential() ? Html::a('<span class="glyphicon glyphicon-upload"></span>', $url,
                                ['title' => 'Credentials', 'class' => 'danger', 'data-pjax' => '0',
                                    'data-target'=>'#myModal','data-toggle'=>'modal']) : false;
                        }
                    ],
            ]
        ],
    ]); ?>
<?php
    Modal::begin([
        'header' => '<h2>Укажите json файл креденшиалс</h2>',
        'id'=>'myModal'
    ]);
    $form = ActiveForm::begin(['id' => 'credential-form', 'options' => ['enctype' => 'multipart/form-data']]);
    echo $form->field($json, 'secret_file')->fileInput();

    ActiveForm::end();

    Modal::end();
?>
</div>

<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Mailbox;
use app\models\Server;
use yii\helpers\ArrayHelper;

$servers = Server::find()->all();
$listData=ArrayHelper::map($servers,'id','name');

/* @var $this yii\web\View */
/* @var $model app\models\Mailbox */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="mailbox-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'server_id')->dropDownList($listData, ['prompt'=>'Другой...']) ?>

    <?= $form->field($model, 'host')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'port')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'is_ssl')->dropDownList(Mailbox::$yes_no) ?>

    <?= $form->field($model, 'is_deleted')->dropDownList(Mailbox::$yes_no) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

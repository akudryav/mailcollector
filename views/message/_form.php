<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Message */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="message-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'mailbox_id')->textInput() ?>

    <?= $form->field($model, 'uid')->textInput() ?>

    <?= $form->field($model, 'from_ip')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'from_domain')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'body_text')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'body_html')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'attachment_count')->textInput() ?>

    <?= $form->field($model, 'header')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'message_date')->textInput() ?>

    <?= $form->field($model, 'create_date')->textInput() ?>

    <?= $form->field($model, 'modify_date')->textInput() ?>

    <?= $form->field($model, 'is_ready')->dropDownList(app\models\Message::$yes_no) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

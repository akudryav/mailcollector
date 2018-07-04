<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\AutoComplete;
use app\models\Vertical;
use app\models\Mailbox;
use app\models\Server;
use yii\helpers\ArrayHelper;

//фомируем список вертикалей
$listdata=Vertical::find()
    ->select(['id as value', 'name as label'])
    ->asArray()
    ->all();
?>

<div class="mailbox-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'password')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'backup_email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'phone')->textInput() ?>
    
    <?= $form->field($model, 'buyer')->textInput(['maxlength' => true]) ?>
    
    <?= $form->field($model, 'vertical_id')->widget(
    AutoComplete::className(), [            
        'clientOptions' => [
            'source' => $listdata,
        ],
        'options'=>[
            'class'=>'form-control'
        ]
    ]) ?>

    <?= $form->field($model, 'is_deleted')->dropDownList(app\components\MailHelper::$yes_no) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

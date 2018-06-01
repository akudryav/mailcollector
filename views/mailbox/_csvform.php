<?php
use yii\widgets\ActiveForm;
?>
<div id="csvform">
<?php $form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
    'action'=>['mailbox/import'],
    ]) ?>
    <?= $form->field($model, 'csvFile')->fileInput() ?>
    <button>Загрузить</button>
<?php ActiveForm::end() ?>
</div>
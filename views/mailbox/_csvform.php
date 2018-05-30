<?php
use yii\widgets\ActiveForm;
?>
<div id="csvform">
    <div class="alert alert-info" role="alert">
    <p>CSV файл должен содержать строки следующего вида:</p>
    <p>user@server.xx, password, imap.srv.xx, 123, 1</p>
    </div>
<?php $form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
    'action'=>['mailbox/import'],
    ]) ?>
    <?= $form->field($model, 'csvFile')->fileInput() ?>
    <button>Загрузить</button>
<?php ActiveForm::end() ?>
</div>
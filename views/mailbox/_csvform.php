<?php
use yii\widgets\ActiveForm;
use yii\jui\AutoComplete;
use app\models\Vertical;

//фомируем список вертикалей
$listdata=Vertical::find()
    ->select(['id as value', 'name as label'])
    ->asArray()
    ->all();

?>
<div id="csvform">
<?php $form = ActiveForm::begin([
    'options' => ['enctype' => 'multipart/form-data'],
    'action'=>['mailbox/import'],
    ]);
    echo $form->field($model, 'csvFile')->fileInput();
    echo $form->field($model, 'vertical')->widget(
    AutoComplete::className(), [            
        'clientOptions' => [
            'source' => $listdata,
        ],
        'options'=>[
            'class'=>'form-control'
        ]
    ]);
?>
    <button>Загрузить</button>
<?php ActiveForm::end() ?>
</div>
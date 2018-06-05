<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Vertical */

$this->title = 'Изменение Вертикали: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Все Вертикали', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изменить';
?>
<div class="vertical-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Server */

$this->title = 'Изменение сервера: ' . $model->host;
$this->params['breadcrumbs'][] = ['label' => 'Все Серверы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->host, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="server-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

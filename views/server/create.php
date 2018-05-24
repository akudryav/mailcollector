<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Server */

$this->title = 'Добавить Сервер';
$this->params['breadcrumbs'][] = ['label' => 'Все Серверы', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="server-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

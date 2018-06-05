<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Vertical */

$this->title = 'Добавление Вертикали';
$this->params['breadcrumbs'][] = ['label' => 'Verticals', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vertical-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Mailbox */

$this->title = 'Изменение Аккаунта: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Все Аккаунты', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="mailbox-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    
    <?php if(empty($token)) {
        echo Html::a('Получить токен', ['mailbox/token', 'id' => $model->id], [
                'class' => 'btn btn-warning', 
                ]);
    } ?>

</div>

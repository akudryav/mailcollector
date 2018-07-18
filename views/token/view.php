<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Token */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Все Токены', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="token-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы уверены что хотите удалить данные?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'mailbox_id',
            'access_token',
            'secret_file',
        ],
    ]) ?>

</div>

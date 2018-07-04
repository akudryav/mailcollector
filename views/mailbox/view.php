<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Mailbox */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Все Аккаунты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mailbox-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Изменить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
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
            'email:email',
            'password',
            'backup_email:email',
            'buyer',
            'phone',
            'vertical.name',
            [
                'attribute'=>'is_deleted',
                'value' => app\components\MailHelper::yesOrNo($model->is_deleted),
            ],
            'check_time:datetime',
        ],
    ]) ?>

</div>

<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Message */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Все Письма', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="message-view">

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
            'mailbox.email',
            'uid',
            'from_ip',
            'from_domain',
            [
                'label' => 'Адреса',
                'format' => 'html',
                'value' => $model->showAddresses(),
            ],
            'subject',
            'body_text:ntext',
            'body_html:html',
            'attachment_count',
            [
                'label' => 'Вложения',
                'format' => 'html',
                'value' => $model->showAttachments(),
            ],
            'header:ntext',
            'message_date:datetime',
            'create_date:datetime',
            'modify_date:datetime',
            [
                'attribute'=>'is_ready',
                'value' => $model->statusName(),
            ],
            'language',
        ],
    ]) ?>

</div>

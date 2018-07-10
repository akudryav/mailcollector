<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Mailbox */

$this->title = 'Получение Oauth токена';
$this->params['breadcrumbs'][] = ['label' => 'Mailboxes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mailbox-view">

    <?php
    if(!empty($authUrl)) {
        echo Html::a('Перейдите по ссылке <span class="glyphicon glyphicon-new-window"></span>', $authUrl, ['target'=>'_blank']);
        echo '<p>И Разрешите доступ приложению</p>';
    }

    if(!empty($wrongCred)) {
        echo 'Используются некорректный Идентификатор клиентов OAuth 2.0<br>';
    }

    echo Html::a('Вернуться к списку аккаунтов', ['mailbox/index']);
    ?>

</div>

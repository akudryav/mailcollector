<?php

/* @var $this yii\web\View */
use yii\helpers\Html;
$this->title = 'Spam Analytics';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>Добро пожаловать</h1>

        <p class="lead">В систему аналитики спам рассылок.</p>

    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h2>Письма</h2>

                <p>Перечень загруженных в систему писем.</p>
                <p><?= Html::a('Открыть', ['message/index'], ['class' => 'btn btn-default']) ?></p>
            </div>
            <div class="col-lg-4">
                <h2>Аккаунты</h2>

                <p>Добавление и редактирование почтовых аккаунтов для сборки почты.</p>
                <p><?= Html::a('Открыть', ['mailbox/index'], ['class' => 'btn btn-default']) ?></p>
            </div>
            <div class="col-lg-4">
                <h2>Настройки</h2>

                <p>Дополнительные настройки системы.</p>
                <p><?= Html::a('Открыть', ['settings/index'], ['class' => 'btn btn-default']) ?></p>
            </div>
        </div>

    </div>
</div>

<?php

use yii\helpers\Html;
use yii\bootstrap\Tabs;

/* @var $this yii\web\View */
/* @var $model app\models\Mailbox */

$this->title = 'Логи системы';
$this->params['breadcrumbs'][] = 'Логи';
?>
<div class="logs-view">

    <?php
    echo Tabs::widget([
        'items' => [
            [
                'label' => 'Почтовый лог',
                'content' => Html::textarea ( 'mail', $maillog, ['rows' => '35', 'cols' => '180'] ),
                'active' => true // указывает на активность вкладки
            ],
            [
                'label' => 'Системный лог',
                'content' => Html::textarea ( 'app', $applog, ['rows' => '35', 'cols' => '180'] ),
            ]
            
        ]
    ]);
    ?>

</div>


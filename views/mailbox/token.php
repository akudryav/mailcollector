<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Mailbox */

$this->title = 'Test Oauth';
$this->params['breadcrumbs'][] = ['label' => 'Mailboxes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mailbox-view">

    <?php 
    if(!empty($results)) {
        if (count($results->getLabels()) == 0) {
            print "No labels found.\n";
        } else {
          print "Labels:\n";
          foreach ($results->getLabels() as $label) {
            printf("- %s\n", $label->getName());
          }
        }
    }
    
    if(!empty($authUrl)) {
        echo Html::a('Перейдите по ссылке', $authUrl, ['target'=>'_blank']);
        echo 'И введите полученный код в поле ниже';
        echo Html::beginForm();
        echo Html::input('text', 'authCode'); 
        echo Html::submitButton('Отправить', ['class' => 'submit']);
        echo Html::endForm();
    }
    ?>

</div>

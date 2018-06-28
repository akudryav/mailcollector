<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

class MessageSearch extends Message
{
    public $email;
    
    public function rules()
    {
        // только поля определенные в rules() будут доступны для поиска
        return [
            [['id', 'attachment_count'], 'integer'],
            [['email', 'label', 'language', 'mailer', 'ip_type', 'subject'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Message::find()->joinWith('mailbox');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
        ]);

        // загружаем данные формы поиска и производим валидацию
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // изменяем запрос добавляя в его фильтрацию
        $query->andFilterWhere(['id' => $this->id])
            ->andFilterWhere(['attachment_count' => $this->attachment_count])
            ->andFilterWhere(['label' => $this->label])
            ->andFilterWhere(['ip_type' => $this->ip_type])
            ->andFilterWhere(['language' => $this->language]);
        $query->andFilterWhere(['like', 'mailbox.email', $this->email])
            ->andFilterWhere(['like', 'mailer', $this->mailer])
            ->andFilterWhere(['like', 'subject', $this->subject]);

        return $dataProvider;
    }
}
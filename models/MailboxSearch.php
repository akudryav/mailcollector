<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;

class MailboxSearch extends Mailbox
{
    public function rules()
    {
        // только поля определенные в rules() будут доступны для поиска
        return [
            [['id'], 'integer'],
            [['email', 'buyer', 'phone'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Mailbox::find();
        if(!Yii::$app->user->identity->isAdmin()) {
            $query->where(['user_id' => Yii::$app->user->identity->id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['email'=>SORT_ASC]]
        ]);

        // загружаем данные формы поиска и производим валидацию
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // изменяем запрос добавляя в его фильтрацию
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'buyer', $this->buyer])
            ->andFilterWhere(['like', 'phone', $this->phone]);

        return $dataProvider;
    }
}
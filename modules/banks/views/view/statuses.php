<?php
use app\modules\banks\models\Status;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $statusesProvider yii\data\ActiveDataProvider */

echo GridView::widget([
    'dataProvider' => $statusesProvider,
    'summary' => '',
    'emptyText' => 'The company have not parsed yet',
    'columns' => [
        [
            'attribute' => 'status',
            'options' => ['width' => '150'],
            'value' => function($company) {
                return Status::getInfo($company->status);
            },
        ],
        [
            'attribute' => 'status_text',
            'label' => 'Text'
        ],
        [
            'attribute' => 'created_at',
            'options' => ['width' => '150'],
            'label' => 'Run',
            'format' => 'datetime'
        ]
    ]
]);

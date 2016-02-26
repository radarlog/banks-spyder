<?php
use app\modules\banks\models\Status;
use yii\bootstrap\Modal;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $bank \app\modules\banks\models\AbstractBank */
/* @var $companyProvider yii\data\ActiveDataProvider */

$title = "Companies of {$bank->name}";
$parcedOn = Yii::$app->formatter->asDate($bank->created_at);

$this->title = "{$title} / " . Yii::$app->name;

?>
<div class="main-view-companies">
    <div class="body-content">

        <h1><?= Html::encode("$title on {$parcedOn}"); ?></h1>
        <br/>

        <?php

        Modal::begin([
            'header' => "<h3>Company <span id='modal-company-name'></span></h3>",
            'id' => 'modal-company-statuses',
            'size' => Modal::SIZE_LARGE,
        ]);
        Modal::end();

        Pjax::begin(['id' => 'companies']);
        echo GridView::widget([
            'dataProvider' => $companyProvider,
            'emptyText' => 'No parsed companies found.',
            'showOnEmpty' => false,
            'columns' => [
                'name',
                'risk',
                'currency',
                [
                    'label' => 'Period',
                    'value' => function($company) {
                        return "{$company->period} days";
                    },
                    'format' => 'raw'
                ],
                [
                    'label' => 'Status',
                    'options' => ['width' => '150'],
                    'value' => function($company) {
                        return Status::getInfo($company->status);
                    },
                ],
                [
                    'class' => ActionColumn::className(),
                    'template' => '{statuses}',
                    'contentOptions' => ['class' => 'text-center'],
                    'buttons' => [
                        'statuses' => function ($url, $company, $key) {
                            return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', '#', [
                                'style' => 'outline: 0',
                                'class' => 'company-statuses-view',
                                'data' => [
                                    'toggle' => 'modal',
                                    'target' => '#modal-company-statuses',
                                    'id' => $company->id,
                                    'href' => $url,
                                    'name' => $company->name,
                                    'pjax' => 'companies',
                                ],
                            ]);
                        },
                    ],
                ],
            ],
        ]);
        Pjax::end();
        ?>
    </div>
</div>

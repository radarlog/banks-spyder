<?php
use app\modules\banks\models\Company;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $bankProvider yii\data\ActiveDataProvider */

$this->title = Yii::$app->name;

?>
<div class="main-default-index">
    <div class="body-content">

        <?= GridView::widget([
            'dataProvider' => $bankProvider,
            'showHeader' => true,
            'summary' => '',
            'emptyText' => 'No banks found. Please check the config.',
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'headerOptions' => ['width' => '50'],
                ],
                [
                    'header' => 'Bank',
                    'content' => function ($row) {
                        $bid = $row['bid'];
                        /* @var $bankModule \app\modules\banks\Module.php */
                        $bankModule = Yii::$app->getModule('banks');
                        $bank = $bankModule->getBank($bid);
                        return $bank->name;
                    },
                ],
                [
                    'header' => 'Run',
                    'content' => function ($row) {
                        return
                            $row['created_at']
                            ? Yii::$app->formatter->asDatetime($row['created_at'])
                            : Html::tag('span', '', [
                                'class' => "glyphicon glyphicon-remove text-danger"
                            ]);
                    },
                    'options' => ['width' => '200']
                ],
                [
                    'header' => 'Status',
                    'options' => ['width' => '50'],
                    'contentOptions' => ['class' => 'text-center'],
                    'content' => function ($row) {
                        $glyphiconClass = $row['status'] ? 'glyphicon-ok text-success' : 'glyphicon-remove text-danger';
                        return Html::tag('span', '', [
                            'class' => "glyphicon $glyphiconClass",
                            'title' => $row['status_text'],
                        ]);
                    }
                ],
                [
                    'header' => 'Companies',
                    'options' => ['width' => '50'],
                    'contentOptions' => ['class' => 'text-center'],
                    'content' => function ($row) {
                        $count = Company::find()->where(['bank_id' => $row['id']])->count();
                        if($count > 0) {
                            $url = Url::toRoute(['/banks/view/companies', 'id' => $row['id']]);
                            $count = Html::a($count, $url);
                        }
                        return $count;
                    }
                ],
            ],
        ]) ?>

    </div>
</div>

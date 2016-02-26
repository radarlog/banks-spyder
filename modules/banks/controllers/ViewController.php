<?php

namespace app\modules\banks\controllers;

use app\modules\banks\models\AbstractBank;
use app\modules\banks\models\Company;
use app\modules\banks\models\Status;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ViewController extends Controller
{
    public $layout = 'main';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {

        return $this->render('index', [
            'bankProvider' => new ActiveDataProvider([
                'query' => (new Query())->from(AbstractBank::tableName())->orderBy('created_at DESC')
            ])
        ]);
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionCompanies($id)
    {
        $bank = AbstractBank::findOne($id);
        if (!$bank) {
            throw new NotFoundHttpException('Invalid bank id');
        }

        return $this->render('companies', [
            'bank' => $bank,
            'companyProvider' => new ActiveDataProvider([
                'query' => Company::find()->with('statuses')->where(['bank_id' => $bank->id]),
            ])
        ]);
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionStatuses($id)
    {
        $company = Company::findOne(['id' => $id]);
        if (!$company) {
            throw new NotFoundHttpException('Invalid company id');
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderPartial('statuses', [
                'statusesProvider' => new ActiveDataProvider([
                    'query' => Status::find()->where(['company_id' => $id]),
                    'sort' => false,
                ])
            ]);
        }
    }
}
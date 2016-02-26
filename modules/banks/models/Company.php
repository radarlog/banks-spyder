<?php

namespace app\modules\banks\models;

use DateTime;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $bank_id
 * @property string $params
 * @property string $name
 * @property string $currency
 * @property string $risk
 *
 * @property Status[] $statuses
 * @property AbstractBank $bank
 * @property mixed $status
 * @property integer $period
 * @property DateTime $dateFrom
 * @property DateTime $dateTo
 * @property bool $skip
 * @property string $fileName
 * @property string $ApiName
 * @property string $pdf
 * @property bool $apiEnabled
 */

class Company extends ActiveRecord
{
    const PERIOD_IN_DAYS_DEFAULT = 35; // 5 weeks
    const PERIOD_IN_DAYS_HIGH_RISK = 7; // 1 week

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%companies}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bank_id'], 'integer'],
            [['name', 'currency', 'risk'], 'required'],
            [['params'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['currency'], 'string', 'max' => 3],
            [['risk'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bank_id' => 'Bank ID',
            'name' => 'Name',
            'currency' => 'Currency',
            'risk' => 'Risk',
            'params' => 'Params',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(Status::className(), ['company_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBank()
    {
        return $this->hasOne(AbstractBank::className(), ['id' => 'bank_id']);
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        $state = new Status();
        $state->company_id = $this->id;
        if (is_array($status)) {
            $state->status = $status['code'];
            $state->status_text = $status['text'];
        } else {
            $state->status = $status;
        }

        $state->save();
    }

    /**
     * @return bool|int
     */
    public function getStatus()
    {
        $statuses = $this->getStatuses()->select('status')->orderBy('created_at DESC');

        $count = $statuses->count();
        $last = $statuses->limit(1)->scalar();
        $isFatalError = $statuses->where(['status' => Status::ERROR])->count() >= (Status::MAX_ERRORS_COUNT - 1); //for current too

        if ($count == 0) {
            return false;
        } elseif ($isFatalError) {
            return Status::FATAL_ERROR;
        } else {
            return $last;
        } //last
    }

    /**
     * @param $date
     */
    public function setDateFrom($date)
    {
        $date = new DateTime($date);
        $this->dateFrom = $date->modify("- {$this->period} days");
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
        return ($this->risk === 'high') ? self::PERIOD_IN_DAYS_HIGH_RISK : self::PERIOD_IN_DAYS_DEFAULT;
    }

    /**
     * @param $date
     */
    public function setDateTo($date)
    {
        $date = new DateTime($date);
        $this->dateTo = $date;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        if ($this->hasAttribute('params')) {
            $this->params = json_decode($this->params);
        }

        $bank = $this->bank;

        $this->dateFrom = $bank->created_at;
        $this->dateTo = $bank->created_at;

        return parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->hasAttribute('params')) {
            $this->params = json_encode($this->params);
        }

        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    public function getSkip()
    {
        $daysAgo = self::find()
            ->joinWith('bank')
            ->select(['DATEDIFF(CURDATE(), created_at)'])
            ->where([
                'name' => $this->name,
                'currency' => $this->currency
            ])
            ->orderBy('created_at DESC')
            ->limit(1);

        return $daysAgo->exists() && (int)$daysAgo->scalar() < $this->period;
    }

    public function getApiEnabled()
    {
        return isset(Yii::$app->params['ApiUrl']);
    }

    /**
     * @return string
     */
    public function parseRisk()
    {
        $risk = 'high'; //default

        if ($this->apiEnabled) {
            $risk = $this->getApiRisk();
        }

        return $risk;
    }

    /**
     * @return string
     * @throws ParserExceprion
     */
    protected function getApiRisk()
    {
        $cache = Yii::$app->cache;
        $cache->keyPrefix = __FUNCTION__;

        $risk = $cache->get($this->name);
        if (!$risk) {
            $response = Parser::$curl->get(Yii::$app->params['ApiUrl'], [
                'companyName' => $this->ApiName
            ]);

            if ($response->Status !== 'SUCCESS') {
                throw new ParserExceprion("Can't get risk for {$this->name}");
            }

            $risk = $response->RiskRating;
            $cache->set($this->name, $risk);
        }

        return $risk;
    }

    /**
     * @return string
     */
    public function getApiName()
    {
        return preg_replace('/^([^ ]+) .+$/', "$1", $this->name); //get first word
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        $name_parts[] = $this->name;
        $name_parts[] = $this->bank->name;
        $name_parts[] = $this->currency;
        $name_parts[] = sprintf("%s-%s",
            $this->dateFrom->format('d.m.Y'),
            $this->dateTo->format('d.m.Y')
        );

        $filename = implode('#', $name_parts);
        $filename = str_replace(' ', '_', $filename);

        return "{$filename}.pdf";
    }

    /**
     * @return Company
     */
    public static function next()
    {
        $company = self::find()
            ->joinWith([
                'statuses' => function ($query) {
                    $query->onCondition(['status' => Status::hasParsed()]);
                }
            ])
            ->where(['company_id' => null])
            ->limit(1)
            ->one();

        return $company;
    }

    /**
     * @return int
     * @throws ParserExceprion
     */
    public function parseTransactions()
    {
        $status = $this->bank->parseTransactions($this);
        if ($status == Status::OK) {
            $status = $this->savePDF();
        }

        return $status;
    }

    /**
     * @return mixed
     */
    public function getPDF()
    {
        return $this->bank->generatePDF($this);
    }

    /**
     * @return int
     */
    protected function savePDF()
    {
        if ($this->apiEnabled) {
            $saveStatus = $this->saveApiPDF();
        } else {
            $saveStatus = $this->saveFilePDF();
        }

        return $saveStatus;
    }

    /**
     * @return int
     */
    protected function saveFilePDF()
    {
        $fileName = Yii::getAlias('@app/modules/banks/statements/' . $this->fileName);
        $saved = file_put_contents($fileName, $this->pdf);

        return ($saved === false) ? Status::ERROR : Status::OK;
    }

    /**
     * @return int
     * @throws ParserExceprion
     */
    protected function saveApiPDF()
    {
        $data = [
            'companyName' => $this->ApiName,
            'fileBytes' => base64_encode($this->pdf), //download PDF before changing content-type below
            'fileName' => $this->fileName,
        ];

        Parser::$curl->setHeader('Content-Type', 'application/json; charset=utf-8');
        $response = Parser::$curl->post(Yii::$app->params['ApiUrl'], $data);

        if ($response->Status !== 'SUCCESS') {
            throw new ParserExceprion("Can't push PDF file");
        }

        return Status::OK;
    }
}

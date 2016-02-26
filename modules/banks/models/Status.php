<?php

namespace app\modules\banks\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%statuses}}".
 *
 * @property integer $id
 * @property integer $company_id
 * @property integer $status
 * @property string $created_at
 *
 * @property Company $company
 */
class Status extends ActiveRecord
{
    const MAX_ERRORS_COUNT = 10;
    const ERROR = 0;
    const FATAL_ERROR = -1; //stop parsing

    const OK = 1;
    const NO_TRANSACTIONS = 2;

    public static $text = [
        self::OK => 'OK',
        self::ERROR => 'Error',
        self::NO_TRANSACTIONS => 'No Transactions',
        self::FATAL_ERROR => 'Fatal Error',
        '' => 'Not Parsed'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%statuses}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'status'], 'required'],
            [['company_id', 'status'], 'integer'],
            [['status_text'], 'string'],
            [['created_at'], 'safe']
        ];
    }

    /**
     * @return array
     */
    public static function hasParsed()
    {
        return [
            self::OK,
            self::NO_TRANSACTIONS,
            self::FATAL_ERROR
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['id' => 'company_id']);
    }

    /**
     * @param $status
     * @return string
     */
    public static function getInfo($status)
    {
        if ($status !== false) {
            return self::$text[$status];
        }

        return 'Not parsed';
    }
}

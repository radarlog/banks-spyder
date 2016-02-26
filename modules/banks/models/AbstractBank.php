<?php

namespace app\modules\banks\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property string $name
 * @property integer $id
 * @property string $bid
 * @property integer $status
 * @property string $status_text
 * @property string $created_at
 *
 * @property Company[] $companies
 */

abstract class AbstractBank extends ActiveRecord implements Parseable
{
    const ERROR = Status::ERROR;
    const OK = Status::OK;

    public $name = '';
    protected $user;
    protected $pass;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%banks}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bid', 'status'], 'required'],
            [['bid'], 'string', 'max' => 10],
            [['status'], 'integer'],
            [['status_text'], 'string'],
            [['created_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public static function instantiate($row)
    {
        $bid = $row['bid'];
        /* @var $bankModule \app\modules\banks\Module.php */
        $bankModule = Yii::$app->getModule('banks');
        $bank = $bankModule->getBank($bid); //real bank's class

        return $bank;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies()
    {
        return $this->hasMany(Company::className(), ['bank_id' => 'id']);
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param string $pass
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }
}

<?php

namespace app\modules\banks;

use app\modules\banks\models\AbstractBank;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;

/**
 * @property AbstractBank[] $banks
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\banks\controllers';

    private $banks = [];

    /**
     * @param $accounts
     * @throws InvalidConfigException
     */
    public function setAccounts($accounts)
    {
        foreach ($accounts as $bid => $bank) {
            if (empty($bid) || strlen($bid) > 10) {
                throw new InvalidConfigException("Spyder's id is not set or more 10 chars");
            }
            $bank['bid'] = $bid;

            if (empty($bank['user'])) {
                throw new InvalidConfigException("Bank's login username is not set");
            }
            if (empty($bank['pass'])) {
                throw new InvalidConfigException("Bank's login password is not set");
            }

            $bank = Yii::createObject($bank);

            if (empty($bank->name)) {
                throw new InvalidConfigException("Bank's name is not set or empty");
            }

            $this->banks[$bid] = $bank;
        }
    }

    /**
     * @return AbstractBank[]
     */
    public function getBanks()
    {
        return $this->banks;
    }

    /**
     * @param $bid
     * @return AbstractBank
     * @throws NotFoundHttpException
     */
    public function getBank($bid)
    {
        if (empty($bid) || !array_key_exists($bid, $this->banks)) {
            throw new NotFoundHttpException('Invalid bank');
        }

        return $this->banks[$bid];
    }
}

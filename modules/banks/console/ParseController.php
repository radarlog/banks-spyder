<?php

namespace app\modules\banks\console;

use app\modules\banks\models\Company;
use app\modules\banks\models\Parser;
use app\modules\banks\models\ParserExceprion;
use app\modules\banks\models\Status;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class ParseController extends Controller
{
    public $defaultAction = 'banks-clients';

    /**
     * @inheritdoc
     */
    public function init()
    {
        new Parser(); //init parser
    }

    /**
     * Retrieve all companies to be parsed from all your accounts
     * @return int
     */
    public function actionBanksClients() {
        /* @var $bankModule \app\modules\banks\Module.php */
        $bankModule = Yii::$app->getModule('banks');
        $banks = $bankModule->banks;

        //TODO: use queries
        foreach ($banks as $bank) {
            try {
                $bank->status = $bank->parseClients();
            } catch (ParserExceprion $e) {
                $bank->status = $bank::ERROR;
                $bank->status_text = $e->getMessage();
            }
            $bank->save();

            $count = count($bank->companies);
            $this->stdout("Parsed with status {$bank->status} {$bank->name}. {$count} companies was added\n", Console::BOLD);
        }

        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * Parse next unparsed company
     * @return int
     */
    public function actionNextCompany() {
        $company = Company::next();
        if(!$company) {
            $this->stdout("No companies found\n", Console::BOLD);
            return Controller::EXIT_CODE_ERROR;
        }

        //TODO: use queries
        try {
            $company->status = $company->parseTransactions();
        } catch (ParserExceprion $e) {
            $company->status = [
                'code' => ($company->status == Status::FATAL_ERROR) ? Status::FATAL_ERROR : Status::ERROR,
                'text' => $e->getMessage()
            ];
        }

        $this->stdout("Parsed {$company->name} with status {$company->status}\n", Console::BOLD);

        return Controller::EXIT_CODE_NORMAL;
    }
}

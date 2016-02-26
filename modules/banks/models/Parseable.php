<?php

namespace app\modules\banks\models;

interface Parseable
{
    /**
     * @return int
     * @throws \yii\db\Exception
     */
    function parseClients();

    /**
     * @param Company $company
     * @return int
     */
    function parseTransactions(Company $company);

    /**
     * @param Company $company
     * @return string
     */
    function generatePDF(Company $company);
}
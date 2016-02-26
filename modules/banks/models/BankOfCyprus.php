<?php

namespace app\modules\banks\models;

use finfo;
use Yii;

class BankOfCyprus extends AbstractBank
{
    public $name = 'Bank Of Cyprus';

    private $loginUrl = 'https://online.bankofcyprus.com/netteller-web/Login.faces';
    private $clientsUrl = 'https://online.bankofcyprus.com/netteller-web/TransactionHistory.faces';
    private $csrfToken;

    /**
     * @throws ParserExceprion
     */
    private function doLogin()
    {
        $response = Parser::$curl->get($this->loginUrl);
        $html = Parser::$html->load($response);
        $this->csrfToken = $this->parseToken($html);

        Parser::$curl->post($this->loginUrl, [ //login and set cookies
            'form1' => 'form1',
            'form1:userIdTxt' => $this->user,
            'form1:passwordTf' => $this->pass,
            'form1:languageDdRich' => 'NETTELLER-LANGUAGE-EN               ',
            'form1:loginBtn' => 'Log-in',
            'javax.faces.ViewState' => $this->csrfToken,
        ]);

        $redirectUrl = Parser::$curl->responseHeaders['Location']; //get redirect location

        if (empty($redirectUrl)) {
            throw new ParserExceprion("Can't login");
        }
    }

    /**
     * @param $data
     * @return string
     * @throws ParserExceprion
     */
    private function parseToken($data)
    {
        $token = '';

        if ($data instanceof \simple_html_dom) //html
        {
            $token = $data->find('input[id=javax.faces.ViewState]', 0)->value;
        } //find token
        elseif ($data instanceof \SimpleXMLElement) //xml
        {
            $token = (string)$data->xpath("/partial-response/changes/update[@id='javax.faces.ViewState']")[0];
        }

        if (empty($token)) {
            throw new ParserExceprion("Can't get token");
        }

        return $token;
    }

    /**
     * @return string
     * @throws ParserExceprion
     */
    private function getClientsPage()
    {
        $response = Parser::$curl->get($this->clientsUrl);
        if (Parser::$curl->responseHeaders['Location'] == $this->loginUrl) { //session expire
            $this->doLogin();
            $response = Parser::$curl->get($this->clientsUrl);
        }

        $html = Parser::$html->load($response);
        $this->csrfToken = $this->parseToken($html);

        return $html;
    }

    /**
     * @inheritdoc
     * @throws ParserExceprion
     */
    public function parseClients()
    {
        $html = $this->getClientsPage();

        $js = $html->search_noise('new RichFaces.ui.Select("accountDetailPanel:j_idt'); //get raw html with clients
        $clients = Parser::getJsonArray($js, 'clientSelectItems');

        //debug
        //$clients = array(0 => array('id' => 'accountDetailPanel:j_idt77:j_idt77Item0', 'label' => '357010219510 (Sight Account - EUR - FEKOTRIS INVESTMENTS LIMITED )', 'value' => 'e0579306-ad71-4997-b9b6-c1dc0d530a96'));

        if (empty($clients)) {
            throw new ParserExceprion("Can't find clients");
        }

        $this->status = self::OK;
        $this->save(); //get ID for linking

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($clients as $client) {
                if (preg_match('/^(\d+) \(Sight Account - (\w+) - (.+) \)$/', $client['label'], $m)) {
                    $companyName = $m[3];
                    $currency = $m[2];

                    $company = new Company();
                    $company->name = $companyName;
                    $company->currency = $currency;
                    $company->risk = $company->parseRisk();

                    if ($company->skip) {
                        continue; //do not add
                    }

                    $company->link('bank', $this);

                    $company->params = [
                        'hash' => $client['value'],
                        'htmlId' => $this->parseHtmlId($client['id']),
                        'raw' => $client['label'],
                    ];

                    $company->save();
                }
            }
            $transaction->commit();
        } catch (ParserExceprion $e) {
            $transaction->rollBack();
            throw $e;
        }

        return self::OK;
    }

    /**
     * @param $htmlId
     * @return string
     * @throws ParserExceprion
     */
    private function parseHtmlId($htmlId)
    {
        if (!preg_match('/(accountDetailPanel:j_idt\d+:j_idt\d+)Item\d+/', $htmlId, $m)) {
            throw new ParserExceprion("Can't parse HtmlId");
        }

        return $m[1];
    }

    /**
     * @param Company $company
     * @param array $request
     * @return string
     * @throws ParserExceprion
     */
    private function ajaxFormUpdate(Company $company, array $request)
    {
        $default = [
            'form' => 'form',
            $company->params->htmlId => $company->params->hash,
            'javax.faces.ViewState' => $this->csrfToken,
            'javax.faces.behavior.event' => 'selectitem',
        ];

        $request = array_merge($default, $request);

        Parser::$curl->setHeader('Faces-Request', 'partial/ajax');
        $response = Parser::$curl->post($this->clientsUrl, $request);
        Parser::$curl->unsetHeader('Faces-Request');
        $this->csrfToken = $this->parseToken($response);

        return $response;
    }

    /**
     * @inheritdoc
     * @throws ParserExceprion
     */
    public function parseTransactions(Company $company)
    {
        $this->getClientsPage();

        //select company
        $this->ajaxFormUpdate($company, [
            'javax.faces.source' => $company->params->htmlId,
            'org.richfaces.ajax.component' => $company->params->htmlId
        ]);

        //select other period
        $this->ajaxFormUpdate($company, [
            'j_idt92:j_idt92' => 'CYREF134                            ', //other period
            'javax.faces.source' => 'j_idt92:j_idt92',
            'javax.faces.behavior.event' => 'selectitem',
            'org.richfaces.ajax.component' => 'j_idt92:j_idt92'
        ]);

        //select date's range
        $response = $this->ajaxFormUpdate($company, [
            'fromDateCal:fromDateCalInputDate' => $company->dateFrom->format('d/m/Y'),
            'toDateCal:toDateCalInputDate' => $company->dateTo->format('d/m/Y'),
            'javax.faces.source' => 'j_idt99:j_idt99',
            'org.richfaces.ajax.component' => 'j_idt99:j_idt99',
            'j_idt99:j_idt99' => 'j_idt99:j_idt99'
        ]);

        $this->checkErrors($response);

        if ($this->hasTransactions($response)) {
            $status = Status::OK;
        } else {
            $status = Status::NO_TRANSACTIONS;
        }

        return $status;
    }

    /**
     * @param $xml
     * @return bool
     * @throws ParserExceprion
     */
    private function checkErrors($xml)
    {
        if ($xml instanceof \SimpleXMLElement) {
            //has error message
            $errorId = 'j_idt69';
            $html = (string)$xml->xpath("/partial-response/changes/update[@id='{$errorId}']")[0]; //error message
            $text = Parser::$html->load($html)->find("span[id={$errorId}]", 0)->plaintext; //find text
            $text = trim($text);
            if (!empty($text)) {
                throw new ParserExceprion("Bank's error: {$text}");
            }

            //has daily limit exceeded
            $html = (string)$xml->xpath("/partial-response/changes/update[@id='transactionTable:nettellerDataTable']")[0];
            $text = Parser::$html->load($html)->find('table td[id=transactionTable:nettellerDataTable:0:descriptionCol]',
                0)->plaintext; //find text
            $pattern = '/The maximum daily permissible number of transaction history requests for this account has been exceeded/';
            if (preg_match($pattern, $text)) {
                throw new ParserExceprion("Daily requests limit has been exceeded");
            }
        }

        return false;
    }

    /**
     * @param $xml
     * @return bool
     */
    private function hasTransactions($xml)
    {
        if ($xml instanceof \SimpleXMLElement) {
            $html = (string)$xml->xpath("/partial-response/changes/update[@id='transactionTable:nettellerDataTable']")[0];
            $text = Parser::$html->load($html)->find('[id=transactionTable:nettellerDataTable:tb]', 0)->plaintext;
            if (trim($text) == 'No Items Found') {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws ParserExceprion
     */
    public function generatePDF(Company $company)
    {
        //download PDF
        $response = Parser::$curl->post($this->clientsUrl, [
            'form' => 'form',
            $company->params->htmlId => $company->params->hash,
            'javax.faces.ViewState' => $this->csrfToken,
            'fromDateCal:fromDateCalInputDate' => $company->dateFrom->format('d/m/Y'),
            'toDateCal:toDateCalInputDate' => $company->dateTo->format('d/m/Y'),
            'j_idt49:saveTypeDd' => 'pdf',
            'j_idt49:j_idt61' => 'Export',
        ]);

        //check is PDF
        $finfo = new finfo(FILEINFO_MIME_TYPE); // return mime type
        if ($finfo->buffer($response) == 'application/pdf') {
            return $response;
        }

        throw new ParserExceprion("Can't download PDF file");
    }
}

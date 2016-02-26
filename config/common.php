<?php

use yii\helpers\ArrayHelper;

$params = ArrayHelper::merge(
    require(__DIR__ . '/params.php'),
    @include(__DIR__ . '/params-local.php')
);

return [
    'name' => 'Banks Spyder',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'modules' => [
        'banks' => [
            'class' => 'app\modules\banks\Module',
            'accounts' => [
                'boc' => [
                    'class' => 'app\modules\banks\models\BankOfCyprus',
                    'user' => '',
                    'pass' => ''
                ],
            ]
        ],
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'charset' => 'utf8',
            'on afterOpen' => function($event) {
                $event->sender->createCommand("SET time_zone = '+00:00'")->execute(); //set mysql default zone as UTC
            }
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'bank/<id:\d+>' => 'banks/view/companies',
                'company/<id:\d+>' => 'banks/view/statuses',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\ArrayCache',
        ],
        'log' => [
            'class' => 'yii\log\Dispatcher',
        ],
        'formatter' => [
            'timeZone' => 'Europe/Minsk',
            'dateFormat' => 'php:d.m.Y',
            'timeFormat' => 'php:H:i:s',
            'datetimeFormat' => 'php:d.m.Y H:i:s',
            'nullDisplay' => '',
        ],
    ],
    'params' => $params,
];
<?php

return [
    'id' => 'app',
    'defaultRoute' => 'banks/view/index',
    'components' => [
        'errorHandler' => [
            'errorAction' => 'banks/view/error',
        ],
        'request' => [
            'cookieValidationKey' => '',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
        ],
    ],
];

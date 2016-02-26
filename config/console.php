<?php

return [
    'id' => 'app-console',
    'controllerNamespace' => 'app\modules\banks\console',
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@app/modules/banks/migrations'
        ],
    ],
];

<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 02.09.2015
 * Time: 22:23
 */

return [
    'server' => [
        'provider' => [
            'address' => 'localhost',
            'port' => 8008,
        ],
        'auth' => [
            'class' => '\vitprog\wamp\server\AuthProvider',
        ],
        'internal' => [
            'realm' => 'realm1',
            'controllers' => [],
        ],
    ],
    'yii' => [
        'id' => 'wamp server',
        'basePath' => __DIR__,
        'components' => [
            'errorHandler' => [
                'class' => 'yii\console\ErrorHandler',
            ],
        ],
        'wampCache' => [
            'class' => 'yii\caching\FileCache'
        ]
    ],
];
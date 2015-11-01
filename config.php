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
        'internal' => [
            'realm' => 'realm1',
            'controllers' => [],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache'
        ]
    ],
    'yii' => [
        'id' => 'wamp server',
        'basePath' => __DIR__,
        'components' => [
            'errorHandler' => [
                'class' => 'yii\console\ErrorHandler',
            ],
        ],
    ],
];
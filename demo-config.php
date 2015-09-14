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
            'port' => 8080,
        ],
        'auth' => [],
        'internal' => [
            'realm' => 'realm1',
            'controllers' => [],
        ],
    ],
    'yii' => [
        'id' => 'wamp demo',
        'basePath' => __DIR__,
        'components' => [
            'errorHandler' => [
                'class' => 'yii\console\ErrorHandler',
            ],
        ],
    ],
];
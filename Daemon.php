<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 10.09.15
 * Time: 14:52
 */

namespace vitprog\wamp;

use \Thruway\Peer\Router;
use yii\caching\FileCache;
use \yii\helpers\ArrayHelper;


class Daemon {

    public $router;
    public $internal;
    public $provider;

    protected static $_instance;

    function __construct(array $config) {

        if (static::$_instance !== null) {
            throw new \Exception();
        }

        $providerOptions = ArrayHelper::getValue($config, 'server.provider', []);
        $internalOptions = ArrayHelper::getValue($config, 'server.internal', []);
        $cacheConfig = ArrayHelper::getValue($config, 'server.cache', []);

        $yiiAppClass = ArrayHelper::getValue($config, 'server.yiiAppClass', 'vitprog\wamp\server\YiiWampApplication');
        $providerClass = ArrayHelper::getValue($providerOptions, 'class', '\Thruway\Transport\RatchetTransportProvider');
        $internalClass = ArrayHelper::getValue($internalOptions, 'class', '\vitprog\wamp\server\InternalClient');
        $cacheClass = ArrayHelper::getValue($cacheConfig, 'class', '\yii\caching\FileCache');

        $realm = ArrayHelper::getValue($config, 'server.realm', 'realm1');


        $basePath = ArrayHelper::getValue($config, 'yii.basePath', null);
        if ($basePath == null) {
            die('yii.basePath not set');
        }
        if (ArrayHelper::getValue($config, 'yii.vendorPath', null) == null) {
            $config['yii']['vendorPath'] = realpath($basePath . '/vendor');
        }
        if (ArrayHelper::getValue($config, 'yii.runtimePath', null) == null) {
            $config['yii']['runtimePath'] = realpath($basePath . '/runtime');
        }

        $app = new $yiiAppClass($config['yii']);

        $this->cache = \Yii::createObject($cacheClass, $cacheConfig);


        $this->router = new Router();

        $this->provider = new $providerClass(
            ArrayHelper::getValue($providerOptions, 'address', 'localhost'),
            ArrayHelper::getValue($providerOptions, 'port', '8080')
        );
        $this->router->addTransportProvider($this->provider);

        $this->internal = new $internalClass($realm);
        \Yii::configure($this->internal, $internalOptions);
        $this->router->addInternalClient($this->internal);



        \Yii::$app->setComponents(
            [
                'wampDeamon' => $this,
                'wampRouter' => $this->router,
                'wampInternal' => $this->internal,
                'wampCache' => $this->cache,
            ]
        );

        static::$_instance = $this;
    }

    public function run() {
        echo 'Yii2 WAMP server run' . PHP_EOL;
        $this->router->start();
    }

    /**
     * @return static
     */
    public static function wamp() {
        return static::$_instance;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 10.09.15
 * Time: 14:52
 */

namespace vitprog\wamp;

use \Thruway\Peer\Router;
use \yii\helpers\ArrayHelper;


class Daemon {

    public $router;
    public $internal;
    public $provider;

    /** @var \Thruway\Module\Module|\Thruway\Authentication\AuthenticationManagerInterface **/
    public $authMng;
    public $auth;

    protected static $_instance;

    function __construct(array $config) {

        if (static::$_instance !== null) {
            throw new \Exception();
        }

        $authOptions = ArrayHelper::getValue($config, 'server.auth', []);
        $providerOptions = ArrayHelper::getValue($config, 'server.provider', []);
        $internalOptions = ArrayHelper::getValue($config, 'server.internal', []);

        $yiiAppClass = ArrayHelper::getValue($config, 'server.yiiAppClass', 'vitprog\wamp\server\YiiWampApplication');
        $authClass = ArrayHelper::getValue($authOptions, 'class', '\vitprog\wamp\server\AuthProvider');
        $providerClass = ArrayHelper::getValue($providerOptions, 'server.provider', '\Thruway\Transport\RatchetTransportProvider');
        $internalClass = ArrayHelper::getValue($internalOptions, 'server.internal', '\vitprog\wamp\server\InternalClient');

        $realm = ArrayHelper::getValue($config, 'server.realm', 'realm1');


        $this->router = new Router();

        if ($authClass != null) {
            $this->authMng = new \Thruway\Authentication\AuthenticationManager();
            $this->router->setAuthenticationManager($this->authMng);
            $this->router->addInternalClient($this->authMng);

            $this->auth = new $authClass(ArrayHelper::getValue($authOptions, 'realms', ["*"]));
            $this->router->addInternalClient($this->auth);
        }

        $this->provider = new $providerClass(
            ArrayHelper::getValue($providerOptions, 'address', 'localhost'),
            ArrayHelper::getValue($providerOptions, 'port', '8080')
        );
        $this->router->addTransportProvider($this->provider);

        $this->internal = new $internalClass($realm);
        \Yii::configure($this->internal, $internalOptions);
        $this->router->addInternalClient($this->internal);

        new $yiiAppClass($config['yii']);
        \Yii::$app->setComponents(
            [
                'wampDeamon' => $this,
                'wampRouter' => $this->router,
                'wampInternal' => $this->internal,
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
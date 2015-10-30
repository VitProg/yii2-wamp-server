<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 06.09.2015
 * Time: 21:02
 */

namespace vitprog\wamp\server;


use vitprog\wamp\components\WampController;
use Thruway\Peer\Client;
use yii\caching\Cache;
use yii\helpers\Console;
use yii\helpers\VarDumper;

/**
 * Class InternalClient
 * @package app\server
 *
 * todo
 * base class auto functions:
 * public function onCall{Name}
 * public function onSubscribe{Name}
 * public function onUnSubscribe{Name}
 * public function onOpen
 * public function onClose
 * public function onError
 *
 */
class InternalClient extends Client {

    public $realm = 'realm1';
    public $controllers = [];

    /**
     * List sessions info
     * @var array
     */
    protected $_sessions = [];

    protected $_controllers = [];

    function __construct($realm = null) {
        if ($realm) {
            $this->realm = $realm;
        }
        parent::__construct($this->realm);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     * @throws \yii\base\InvalidConfigException
     */
    public function onSessionStart($session, $transport) {
        echo "--------------- Hello from InternalClient ------------" . PHP_EOL;
        try {

            $session->subscribe('wamp.metaevent.session.on_join',  [$this, 'onSessionJoin']);
            $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);

            // инициализация методов
            foreach ($this->controllers as $controllerClass) {

                $controller = \Yii::createObject($controllerClass);

                if ($controller && $controller instanceof WampController) {
                    /** @var $controller WampController */

                    foreach ($controller->registers() as $callUri => $callOptions) {
                        if (is_array($callOptions) === false) {
                            $callOptions = [$callOptions];
                        }
                        if (is_numeric($callUri) || empty($callOptions[0])) {
                            // todo throw exception
                            continue;
                        }
                        $procedureName = $controller->getUri($callUri);
                        $callback = [$controller, $callOptions[0]];
                        unset($callOptions[0]);
                        $options = empty($callOptions) ? $callOptions : [];
//                        $this->getCallee()->register(
//                            $this->getSession(),
                        $session->register(
                            $procedureName,
                            $callback,
                            $options
                        );
                    }

                    foreach ($controller->subscribes() as $subscribeUri => $subscribeOptions) {
                        if (is_array($subscribeOptions) === false) {
                            $subscribeOptions = [$subscribeOptions];
                        }
                        if (is_numeric($subscribeUri) || empty($subscribeOptions[0])) {
                            // todo throw exception
                            continue;
                        }
                        $topicName = $controller->getUri($subscribeUri);
                        $callback = [$controller, $subscribeOptions[0]];
                        unset($subscribeOptions[0]);
                        $options = empty($subscribeOptions) ? $subscribeOptions : [];
//                        $this->getSubscriber()->subscribe(
//                            $this->getSession(),
                        $session->subscribe(
                            $topicName,
                            $callback,
                            $options
                        );
                    }

                    $this->_controllers[$controller->getId()] = $controller;
                }
            }

            foreach ($this->_controllers as $controller) {
                $controller->init();
            }

        } catch (\Exception $e) {
            echo Console::renderColoredString('%rError: %w' . (string)$e . '%n') . PHP_EOL;
        }
    }

    public function onSessionJoin($args, $kwArgs, $options) {

        /** @var Cache $cache */
//        $cache = \Yii::$app->wampCache;
//        $cache->set()

        var_dump($args);
        var_dump($kwArgs);
        var_dump($options);

//        $args = json_decode(json_encode($args), true);
//        echo "Session {$args[0]['session']} joinned\n";
//        $this->_sessions[] = $args[0];
    }

    public function onSessionLeave($args, $kwArgs, $options) {
//        $args = json_decode(json_encode($args), true);
//        if (!empty($args[0]['session'])) {
//            foreach ($this->_sessions as $key => $details) {
//                if ($args[0]['session'] == $details['session']) {
//                    echo "Session {$details['session']} leaved\n";
//                    unset($this->_sessions[$key]);
//                    return;
//                }
//            }
//        }
    }

}
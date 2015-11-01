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
use vitprog\wamp\components\WampUserTrait;
use yii\caching\Cache;
use yii\db\ActiveRecord;
use yii\helpers\Console;
use yii\helpers\VarDumper;
use yii\web\IdentityInterface;

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
    public $sessionDuration = 86400; // 1 day

    public $session;

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

            $this->session = $session;

            $session->register('app.auth', [$this, 'onAuth']);
            $session->subscribe('wamp.metaevent.session.on_join',  [$this, 'onSessionJoin']);
            $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);

            // todo регистрировать только после авторизации
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
                        $callback = [$controller, '_call_' . $callOptions[0]];
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
                        $callback = [$controller, '_call_' . $subscribeOptions[0]];
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

    public function onAuth($args, $kwArgs, $options) {
        $sessionId = $kwArgs->sessionId;
        $authToken = $kwArgs->authToken;
        $uid = (int)$kwArgs->uid;

        /** @var ActiveRecord $userClass */
        $userClass = \Yii::$app->user->identityClass;
        /** @var $user ActiveRecord|IdentityInterface|WampUserTrait */
        $user = $userClass::findOne($uid);

        if ($user == null ) {
            return 'user is null';
        }

        if ($user->wampGetAuthToken() != $authToken) {
            VarDumper::dump([$user->wampGetAuthToken(), $authToken, $sessionId, $user->toArray()]);
            return 'authToken';
        }

        $token = $user->wampGenerateToken($sessionId);

        if ($token) {
            $session = new Session($sessionId, $token, $user->id);
            $session->saveSession();
        }

        VarDumper::dump([$kwArgs, $token, $user->toArray()]);

        return [
            'token' => $token,
        ];
    }

    public function onSessionJoin($args, $kwArgs, $options) {
//        VarDumper::dump([$args, $kwArgs, $options]);
        try {
            $roleCheck = isset($args[0]->authroles) && in_array('authenticated_user', $args[0]->authroles);
            $userId = (int)$args[0]->authid;
            $sessionId = (int)$args[0]->session;

            if (!$roleCheck || !$userId || !$sessionId) {
                var_dump('---------------------------------');
                return;
            }

            echo "Session {$sessionId} joinned\n";

        } catch (\Exception $e) {
            echo Console::renderColoredString('%rError: %w' . (string)$e . '%n') . PHP_EOL;
        }
    }

    public function onSessionLeave($args, $kwArgs, $options) {
//        VarDumper::dump([$args, $kwArgs, $options]);
        try {
            $sessionId = (int)$args[0]->session;
//            $this->removeSessionFromList($sessionId);

            echo "Session {$sessionId} leaved\n";

        } catch (\Exception $e) {
            echo Console::renderColoredString('%rError: %w' . (string)$e . '%n') . PHP_EOL;
        }
    }

}
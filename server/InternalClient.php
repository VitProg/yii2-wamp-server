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
    public $sessionDuration = 86400; // 1 day

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

    public function onSessionJoin($args, $kwArgs, $options) {

        try {
            $roleCheck = isset($args[0]->authroles) && in_array('authenticated_user', $args[0]->authroles);
            $userId = (int)$args[0]->authid;
            $sessionId = (int)$args[0]->session;

            if (!$roleCheck || !$userId || !$sessionId) {
                var_dump('---------------------------------');
                return;
            }

            $this->addSessionToList($sessionId, $userId);

            echo "Session {$sessionId} joinned\n";

        } catch (\Exception $e) {
            echo Console::renderColoredString('%rError: %w' . (string)$e . '%n') . PHP_EOL;
        }
    }

    public function onSessionLeave($args, $kwArgs, $options) {
        try {
            $sessionId = (int)$args[0]->session;
            $this->removeSessionFromList($sessionId);

            echo "Session {$sessionId} leaved\n";

        } catch (\Exception $e) {
            echo Console::renderColoredString('%rError: %w' . (string)$e . '%n') . PHP_EOL;
        }
    }


    /// clients sessions

    public function getSessionList() {
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;
        $sessions = $cache->get('wamp_sessions');
        if (!$sessions) {
            $sessions = [];
            $cache->set('wamp_sessions', $sessions, 0);
        }
        return $sessions;
    }

    public function setSessionList($sessions) {
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;
        $cache->set('wamp_sessions', $sessions, 0);
    }

    public function addSessionToList($sessionId, $user) {
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;

        $time = time();

        $sessionData = [
            'session' => $sessionId,
            'user' => $user,
            'time' => $time,
        ];
        $cache->set('wamp_session_' . $sessionId, $sessionData, $this->sessionDuration);
        $sessions = $this->getSessionList();
        $sessions[$sessionId] = $time;
        $this->setSessionList($sessions);
    }

    public function getSessionInList($sessionId) {
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;
        $sessionDate = $cache->get('wamp_session_' . (int)$sessionId);
        return $sessionDate ? $sessionDate : null;
    }

    public function removeSessionFromList($sessionId) {
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;
        $cache->delete('wamp_session_' . (int)$sessionId);

        $sessions = $this->getSessionList();
        unset($sessions[$sessionId]);
        $this->setSessionList($sessions);
    }

}
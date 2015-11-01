<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 07.09.15
 * Time: 17:02
 */

namespace vitprog\wamp\components;


use React\Promise\Promise;
use vitprog\wamp\server\Session;
use \Yii;
use vitprog\wamp\server\InternalClient;
use yii\base\Component;
use yii\base\Controller;
use yii\base\UnknownMethodException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\web\BadRequestHttpException;

abstract class WampController extends Component {

    /** @return string */
    abstract public function getId();

    public function registers() {
        return [];
    }

    public function subscribes() {
        return [];
    }

    /**
     * @param string $to
     * @return string
     */
    public function getUri($to) {
        return $this->getId() . '.' . trim($to, '.');
    }

    /**
     * Get publisher
     * @return \Thruway\Role\Publisher
     */
    public function getPublisher() {
        /** @var InternalClient $internal */
        $internal = Yii::$app->wampInternal;
        return $internal->getPublisher();
    }

    /**
     * Get subscriber
     * @return \Thruway\Role\Subscriber
     */
    public function getSubscriber() {
        /** @var InternalClient $internal */
        $internal = Yii::$app->wampInternal;
        return $internal->getSubscriber();
    }

    /**
     * Get callee
     * @return \Thruway\Role\Callee
     */
    public function getCallee() {
        /** @var InternalClient $internal */
        $internal = Yii::$app->wampInternal;
        return $internal->getCallee();
    }


    /**
     * Get caller
     * @return \Thruway\Role\Caller
     */
    public function getCaller() {
        /** @var InternalClient $internal */
        $internal = Yii::$app->wampInternal;
        return $internal->getCaller();
    }

    /**
     * Get client session
     * @return \Thruway\ClientSession
     */
    public function getSession() {
        /** @var InternalClient $internal */
        $internal = Yii::$app->wampInternal;
        return $internal->getSession();
    }

    /**
     * @return string
     */
    public function getRealm() {
        /** @var InternalClient $internal */
        $internal = Yii::$app->wampInternal;
        return $internal->getRealm();
    }

    /**
     * process publish
     * @param string $topicName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     * @param mixed $options
     * @return Promise
     */
    public function publish($topicName, $arguments = null, $argumentsKw = null, $options = null) {
        if (substr($topicName, 0, 1) == '.') {
            $topicName = $this->getUri($topicName);
        }
        return $this->getPublisher()->publish($this->getSession(), $topicName, $arguments, $argumentsKw, $options);
    }

    /**
     * process subscribe
     * @param string $topicName
     * @param callable $callback
     * @param $options
     * @return Promise
     */
    public function subscribe($topicName, $callback, $options = []) {
        if (substr($topicName, 0, 1) == '.') {
            $topicName = $this->getUri($topicName);
        }
        return $this->getSubscriber()->subscribe($this->getSession(), $topicName, $callback, $options);
    }

    /**
     * process register
     * @param string $procedureName
     * @param callable $callback
     * @param mixed $options
     * @return Promise
     */
    public function register($procedureName, $callback, $options = null) {
        if (substr($procedureName, 0, 1) == '.') {
            $procedureName = $this->getUri($procedureName);
        }
        $this->getCallee()->register($this->getSession(), $procedureName, $callback, $options);
    }

    /**
     * process call
     * @param string $procedureName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     * @param mixed $options
     * @return Promise
     */
    public function call($procedureName, $arguments = [], $argumentsKw = [], $options = []) {
        if (substr($procedureName, 0, 1) == '.') {
            $procedureName = $this->getUri($procedureName);
        }
        $this->getCaller()->call($this->getSession(), $procedureName, $arguments, $argumentsKw, $options);
    }


    public function init() {
    }

    public function __call($name, $params)
    {
        try {

            if (strpos($name, '_call_') === 0) {

                $methodName = substr($name, strlen('_call_'));

                list ($args, $argsKw) = $params;

                if (is_object($argsKw)) {
                    $argsKw = (array)$argsKw;
                };

                if (empty($argsKw) || empty($argsKw['token'])) {
                    // todo disconnect session
                    var_dump('!!!!!!!!!!!!!!!!!!!!!!!!');
                    return false;
                }

                $token = (int)$argsKw['token'];
                $session = Session::getSession($token);

                if ($session == null) {
                    // todo disconect client
                    return ['error' => 'session is null'];
                }

                $user = $session->getUser();

                if ($user == null) {
                    return ['error' => 'user is null'];
                }

                if ($user->wampGenerateToken($session->id) != $token) {
                    return ['error' => 'token!'];
                }

                unset($args['token']);

                if ($this->hasMethod($methodName)) {
                    $params = ['session' => $session];
                    if (!empty($argsKw)/* && ArrayHelper::isAssociative($argsKw)*/) {
                        $params = array_merge($params, $argsKw);
                    }
                    $params['args'] = $args;
                    // todo $currentUserId - фигня какаято
                    $params['userId'] = (int)$session->userId;
                    $params['currentUserId'] = (int)$session->userId;

                    $method = new \ReflectionMethod($this, $methodName);

                    $argsMethod = [];
                    $missing = [];
                    foreach ($method->getParameters() as $param) {

                        $name = $param->getName();
                        $name = str_replace(' ', '', ucwords(implode(' ', explode('-', $name))));
                        $name = mb_strtolower(substr($name, 0, 1)) . substr($name, 1);

                        if (array_key_exists($name, $params)) {
                            if ($param->isArray()) {
                                $argsMethod[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                            } else {
                                $argsMethod[] = $params[$name];
                            }
                            unset($params[$name]);
                        } elseif ($param->isDefaultValueAvailable()) {
                            $argsMethod[] = $param->getDefaultValue();
                        } else {
                            if ($name == 'currentUser' || $name == 'user') {
                                if ($user == null) {
                                    $missing[] = $name;
                                } else {
                                    $argsMethod[] = $user;
                                }
                            } else {
                                $missing[] = $name;
                            }
                        }
                    }

                    if (Yii::$app->requestedParams === null) {
                        Yii::$app->requestedParams = $params;
                    }

                    return call_user_func_array([$this, $methodName], $argsMethod);
                }
            }

            $this->ensureBehaviors();
            foreach ($this->_behaviors as $object) {
                if ($object->hasMethod($name)) {
                    return call_user_func_array([$object, $name], $params);
                }
            }
            throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
        } catch (\Exception $ex) {
            \Yii::getLogger()->log($ex, Logger::LEVEL_ERROR, 'wamp-server');
        }
        return null;
    }

}
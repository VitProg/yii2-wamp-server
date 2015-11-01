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

                list ($args, $argsuments) = $params;

                if (is_object($argsuments)) {
                    $argsuments = (array)$argsuments;
                };

                var_dump($argsuments);

                if (empty($argsuments) || empty($argsuments['token'])) {
                    // todo disconnect session
                    return ['error' => 'token is null'];
                }

                $token = $argsuments['token'];
                $session = Session::getSession($token);

                var_dump($token);
                var_dump($session);

                if ($session == null) {
                    // todo disconect client
                    return ['error' => 'session is null'];
                }

                $user = $session->getUser();
                var_dump($user->toArray());

                if ($user == null) {
                    return ['error' => 'user is null'];
                }

                if ($user->wampGenerateToken($session->id) != $token) {
                    return ['error' => 'token!'];
                }

                unset($args['token']);

                if ($this->hasMethod($methodName)) {
                    var_dump('line:'.__LINE__);
                    $params = ['session' => $session];
                    if (!empty($argsuments)) {
                        $params = array_merge($params, $argsuments);
                    }
                    $params['args'] = $args;
                    $params['userId'] = (int)$session->userId;
                    $params['currentUserId'] = (int)$session->userId;

                    $method = new \ReflectionMethod($this, $methodName);
                    var_dump('line:'.__LINE__);
                    $argsMethod = [];
                    $missing = [];
                    foreach ($method->getParameters() as $param) {
                        var_dump('line:'.__LINE__);
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
                    var_dump('line:'.__LINE__);
                    if (Yii::$app->requestedParams === null) {
                        Yii::$app->requestedParams = $params;
                    }
                    var_dump([
                        '$methodName' => $methodName,
                        '$argsMethod' => $argsMethod,
                        'Yii::$app->requestedParams' => Yii::$app->requestedParams
                    ]);
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
            echo PHP_EOL . $ex->getFile() . ':' . $ex->getLine() . ' - ' . $ex->getMessage() . PHP_EOL;
            \Yii::getLogger()->log($ex, Logger::LEVEL_ERROR, 'wamp-server');
        }
        return null;
    }

}
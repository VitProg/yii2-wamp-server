<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 07.09.15
 * Time: 17:02
 */

namespace vitprog\wamp\components;


use \Yii;
use vitprog\wamp\server\InternalClient;
use yii\base\Component;
use yii\base\Controller;
use yii\helpers\Console;
use yii\helpers\VarDumper;

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

}
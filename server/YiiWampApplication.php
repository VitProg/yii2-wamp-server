<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 09.09.15
 * Time: 16:23
 */

namespace vitprog\wamp\server;


use yii\base\Application;
use yii\base\Request;
use yii\base\Response;
use yii\caching\Cache;

/**
 * Class WampApplication
 * @package app\server
 *
 * @property \Thruway\Peer\Router $wampRouter
 * @property InternalClient $wampInternal
 * @property Cache $wampCache
 *
 */
class YiiWampApplication extends Application {

    public $session;

    public function handleRequest($request) {
        return null;
    }

    public function getSession() {
        return null;
    }

    public function getUser() {
        return null;
    }
}
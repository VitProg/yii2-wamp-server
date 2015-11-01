<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 01.11.2015
 * Time: 14:37
 */

namespace vitprog\wamp\server;


use yii\caching\Cache;

class Session {

    public $id;
    public $token;
    public $user;
    public $time;
    public $data;

    function __construct($id, $token, $user, $data = null) {
        $this->id = (int)$id;
        $this->token = $token;
        $this->user = (int)$user;
        $this->data = $data;
    }

    /**
     * @param string $token
     * @return static|null
     */
    public static function getSession($token){
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;

        $session = $cache->get('wamp_session_' . $token);
        if ($session) {
            return unserialize($session);
        }
        return null;
    }

    public function saveSession() {
        /** @var Cache $cache */
        $cache = \Yii::$app->wampCache;

        $cache->set('wamp_session_' . $this->token, serialize($this));
    }

}
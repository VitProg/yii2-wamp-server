<?php

namespace vitprog\wamp\components;

trait WampUserTrait {

    public static $wampSecret = 'SDFS-2HFA-D2H8-D327';

    public $wampSession;

    public function wampGetAuthToken() {
        return sha1($this->getAuthKey());
    }

    public function wampGenerateToken($sessionId) {
        $token = sha1(md5($this->wampGetAuthToken() . $this->id . $sessionId . self::$wampSecret));
        return $token;
    }

}
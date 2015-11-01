<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 01.11.2015
 * Time: 14:37
 */

namespace vitprog\wamp\server;


use vitprog\wamp\components\WampUserTrait;
use yii\caching\Cache;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;


/** @property $user ActiveRecord|IdentityInterface|\WampUserTrait */
class Session {

    public $id;
    public $token;
    public $userId;
    public $time;
    public $data;

    protected $user;

    function __construct($id, $token, $userId, $data = null) {
        $this->id = (int)$id;
        $this->token = $token;
        $this->userId = (int)$userId;
        $this->data = $data;
    }

    /**
     * @param string $token
     * @return self
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

    /** @return ActiveRecord|IdentityInterface|WampUserTrait */
    public function getUser() {
        if (!$this->user) {
            /** @var ActiveRecord $userClass */
            $userClass = \Yii::$app->user->identityClass;
            /** @var $user ActiveRecord|IdentityInterface|WampUserTrait */
            $user = $userClass::findOne($this->userId);

//            if ($user->wampGenerateToken($user->getAuthKey()) != $this->token) {
//                return false;
//            }
            $this->user = $user;
        }
        return $this->user;
    }

}
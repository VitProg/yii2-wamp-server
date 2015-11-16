<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 17.11.2015
 * Time: 1:03
 */

namespace vitprog\wamp\components;


class WampResult {

    protected function __construct() {}

    public static function success(array $data = []) {
        return [
            'result' => 'ok',
            'data' => $data,
        ];
    }

    public static function failure($msg = null, array $data = []) {
        if ($msg && $msg instanceof WampException) {
            return $msg->toArray();
        } else {
            return [
                'result' => 'error',
                'msg' => $msg,
                'data' => $data,
            ];
        }
    }

}
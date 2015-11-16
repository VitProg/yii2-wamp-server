<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 17.11.2015
 * Time: 1:03
 */

namespace vitprog\wamp\components;


use yii\helpers\ArrayHelper;

class WampResult {

    const RESULT_OK = 'ok';
    const RESULT_ERROR = 'err';

    public $result;
    public $msg;
    public $data;

    protected function __construct() {}

    public static function success(array $data = []) {
        $self = new static();
        $self->result = static::RESULT_OK;
        $self->data = $data;
        return $self;
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

    public function isOk() {
        return $this->result == static::RESULT_OK;
    }

    public function isError() {
        return !$this->isOk();
    }

    public function toArray() {
        if ($this->isOk()) {
            return [
                'result' => static::RESULT_OK,
                'data' => $this->data ? ArrayHelper::toArray($this->data) : null,
            ];
        } else {
            return [
                'result' => static::RESULT_OK,
                'msg' => $this->msg ? $this->msg : null,
                'data' => $this->data ? ArrayHelper::toArray($this->data) : null,
            ];
        }
    }

}
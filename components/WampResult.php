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

    /**
     * @param array $data
     * @return static
     */
    public static function success(array $data = []) {
        $self = new static();
        $self->result = static::RESULT_OK;
        $self->data = $data;
        return $self;
    }

    /**
     * @param null $msg
     * @param array $data
     * @return array
     */
    public static function failure($msg = null, array $data = []) {
        $self = new static();
        $self->result = static::RESULT_ERROR;

        if ($msg && $msg instanceof WampException) {
            $self->msg = $msg->getMessage();
            $self->data = $msg->toArray();
        } else {
            $self->msg = $msg;
            $self->data = $data;
        }
        return $self;
    }

    /**
     * @return bool
     */
    public function isOk() {
        return $this->result == static::RESULT_OK;
    }

    /**
     * @return bool
     */
    public function isError() {
        return !$this->isOk();
    }

    /**
     * @return array
     */
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
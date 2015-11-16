<?php
/**
 * Created by PhpStorm.
 * User: VitProg
 * Date: 17.11.2015
 * Time: 0:37
 */

namespace vitprog\wamp\components;


use Exception;

class WampException extends \Exception {

    public $method;

    /**
     * WampException constructor.
     * @param string $message
     * @param int $code
     * @param string $method
     * @param Exception $previous
     */
    public function __construct($message = null, $code = null, $method = null, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->method = $method;
    }


    public function toArray() {
        $data = [
            'result' => 'error',
            'code' => $this->code,
            'msg' => $this->getMessage(),
        ];
        if (defined('WAMP_DEBUG') && WAMP_DEBUG == true) {
            $data['file'] = $this->file;
            $data['line'] = $this->line;
            $data['method'] = $this->method;
        }
        return $data;
    }

}
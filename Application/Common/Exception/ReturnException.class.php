<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------

namespace Common\Exception;

use Think\Exception;

class ReturnException extends Exception {
    private $result;

    public function __construct($return) {
        $this->result = $return;
    }

    public function getResult() {
        return $this->result;
    }
}
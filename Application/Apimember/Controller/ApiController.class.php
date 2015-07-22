<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------

namespace Apimember\Controller;

use Common\Controller\RestController;

/**
 * MemberAPI公共接口，关于Member API特殊处理放在这里
 * Class ApiController
 * @package Apimember\Controller
 */
abstract class ApiController extends RestController{

    private static $uid  =  0;

    public function _initialize(){
        parent::_initialize();
        if (!$this->allowControl()) {
            $this->uid = $this->getUserId();
            $this->isOL();
        }
    }

    protected function isLogin($token){
        return is_member_login($token);
    }

    final protected function allowControl() {
        $allow = C('APIMEM_ALLOW_ACCESS');
        $check = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);
        if (!empty($allow) && in_array_case($check, $allow)) {
            return true;
        }
        return false;
    }

    protected function isOL() {
        isMEOL($this->getToken());
    }
}
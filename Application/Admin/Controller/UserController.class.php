<?php

namespace Admin\Controller;

/**
 * 后台用户控制器
 * Class UserController
 * @package Admin\Controller
 */
class UserController extends AdminController
{

    /**
     * 普通用户管理首页
     */
    public function member()
    {
        $ucentermember=D('UcenterMember');
        $list = $ucentermember->userList('Member');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }

    /**
     * 商家用户管理首页
     */
    public function merchant()
    {
        $ucentermember=D('UcenterMember');
        $list = $ucentermember->userList('merchant');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '商户信息';
        $this->display();
    }

    /**
     * 管理员用户管理首页
     */
    public function admin()
    {
        $ucentermember=D('UcenterMember');
        $list = $ucentermember->userList('admin');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '管理员信息';
        $this->display();
    }

}
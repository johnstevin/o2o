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
        $map['status'] = array('egt', 0);
        $list = $this->lists('UcenterMember', $map);
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
        $map['status'] = array('egt', 0);
        $list = $this->lists('UcenterMember', $map);
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
        $map=array('status' => array('egt', 1),'is_admin'=>array('egt', 1));
        $list = $this->lists('UcenterMember', $map,'id');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '管理员信息';
        $this->display();
    }

}
<?php

namespace Admin\Controller;

class IndexController extends AdminController {

    public function index(){

        $this->display();

    }

    /**
     * 生成菜单
     * @param array 条件
     */
    public function getMenus($where=array()){

        $menus =D('AuthRule')->getMenus($where);
        $this-> ajaxReturn($menus);
    }

}
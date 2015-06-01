<?php

namespace Admin\Controller;

class IndexController extends AdminController {

    public function index(){

        $this->display();

    }

    /**
     * 获取菜单
     */
    public function Menus()
    {
        //TODO 筛选菜单，url还没处理
        $m=array();
        $this-> ajaxReturn($m);
    }

}
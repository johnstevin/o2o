<?php

namespace Admin\Controller;

class IndexController extends AdminController {

    public function index(){
        $menu_list= json_encode(D('AuthRule')->getMenus());
        $this->assign('menu_list',$menu_list);
        $this->display();

    }

}
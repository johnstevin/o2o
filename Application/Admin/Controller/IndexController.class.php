<?php

namespace Admin\Controller;

class IndexController extends AdminController {

    public function count(){
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display();

    }

    public function index(){
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display();

    }

}
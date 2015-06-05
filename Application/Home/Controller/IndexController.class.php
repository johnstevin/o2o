<?php

namespace Home\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){

        echo '<a href="'.U('Index/login').'">login</a>';

    }

    public function login(){
        echo '<a href="'.U('Index/index').'">index</a>';
    }

}
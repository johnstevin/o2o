<?php
namespace Admin\Controller;

class CategoryController extends AdminController {

    //分类列表
    public function index(){
    //查询出所有的分类
        $tree=D("Category")->getTree(0,'id,sort,title,pid');
//        $tree=M("Category")->select();
//        $tree=D("Category")->info(0,'id,title,pid,sort,list_row,description,display,reply,check,extend,create_time,update_time,status,icon');
        //print_r($tree);
        $this->assign('tree',$tree);
        $this->display();
    }

    //分类添加
    public function add(){
//    $cateadd=M("Category")->create();
//        var_dump($cateadd);
        $this->display();

    }

    //分类编辑
    public function edit(){

        $this->display();

    }

    //分类删除
    public function delete(){

    }

}
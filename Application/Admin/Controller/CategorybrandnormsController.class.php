<?php
namespace Admin\Controller;
use Think\Controller;

class CategorybrandnormsController extends AdminController
{
    /*分类品牌规格表*/
    public function index()
    {
        $Cbn = M('Category_brand_norms')->select();
        $this->assign('_list', $Cbn);
        $this->display("index");
    }
}
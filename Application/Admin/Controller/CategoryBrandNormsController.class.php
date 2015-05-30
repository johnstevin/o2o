<?php
namespace Admin\Controller;
use Think\Controller;

class CategoryBrandNormsController extends AdminController
{
    /*分类品牌规格表*/
    public function index()
    {
        $list = $this->lists('Category_brand_norms');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->display("index");
    }
}
<?php

namespace Admin\Controller;

use Think\Controller;

class NormsController extends AdminController
{
    /**
     * 规格管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index()
    {
        //查询出所有的规格
        $list = $this->lists('Norms');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->display();
    }

    //规格添加
    public function add()
    {
        $Norms = D('Norms');
        if (IS_POST) {
            if (false !== $Norms->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Norms->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }
        $this->assign('Norms');
        $this->display('edit');
    }

    //规格编辑
    public function edit($id = null)
    {
        $Norms = D('Norms');
        if (IS_POST) { //提交表单
            if (false !== $Norms->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Norms->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }
        /* 获取规格信息 */
        $info = $id ? $Norms->info($id) : '';
        $this->assign('info', $info);
        $this->assign('Norms');
        $this->display('edit');

    }

    /**
     * 规格修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error('请选择要操作的数据!');
        }
        switch (strtolower($method)) {
            case 'forbidnorms':
                $this->forbid('Norms');
                break;
            case 'resumenorms':
                $this->resume('Norms');
                break;
            case 'deletenorms':
                $this->delete('Norms');
                break;
            default:
                $this->error('参数非法');
        }
    }

    /* 绑定品牌*/
    public function bindNorms(){
        //得到分类id及品牌id的值
        $category1 = I('category_1');
        $category2 = I('category_2');
        $category3 = I('category_3');
        $brandid = I('brand');
       if($_POST){
           //将选中的所有规格进行关联并存入数据库
           $abnmodel = M("Category_brand_norms");
           $categoryid = "";
           if ($category1 != 0 and $category2 == 0 and $category3 == 0) {
               $categoryid = $category1;
           } elseif ($category1 != 0 and $category2 != 0 and $category3 == 0) {
               $categoryid = $category2;
           } elseif ($category1 != 0 and $category2 != 0 and $category3 != 0) {
               $categoryid = $category3;
           }
           $brand_id = implode(",", $brandid);
           foreach ($_POST['norms_id'] as $norms) {
               $data[] = [
                   'category_id' => $categoryid,
                   'brand_id' => $brand_id,
                   'norms_id' => $norms,
               ];
           }
           $result = $abnmodel->addAll($data);
           if (is_int($result)) {
               $this->redirect('Categorybrandnorms/index', $data, 3, '关联成功,页面跳转中...');
           } else {
               $this->redirect('Norms/bindNorms', $data, 3, '关联成功,页面跳转中...');
           }
       }else{
           //将分类信息和品牌信息分别显示在下拉菜单中
           //   1.根据分类id及品牌id的值返回其对应的title值
           $catemodel = M("Category");
           $cate = $catemodel->field('id,title')->where(array('id' => array('IN', "$category1,$category2,$category3")))->select();
           $brnmodel = M("Brand");
           $brand = $brnmodel->field('id,title')->where(array('id' => array('IN', $brandid)))->select();
           $this->assign('category_1', $cate['0']);
           $this->assign('category_2', $cate['1']);
           $this->assign('category_3', $cate['2']);
           $this->assign('brand', $brand);
           //查询出所有的规格
           $list = $this->lists('Norms');
           int_to_string($list);
           $this->assign('_list', $list);
           $this->display("bindNormsIndex");
       }
    }
}

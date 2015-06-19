<?php

namespace Admin\Controller;

use Think\Controller;

class BrandController extends AdminController
{
    /**
     * 品牌管理首页
     */
    public function index()
    {
        /* 查询条件初始化 */
        $name       =   I('name');
        $map['status']  =   array('egt',-1);
        if(is_numeric($name)){
            $map['id|title']=   array(intval($name),array('like','%'.$name.'%'),'_multi'=>true);
        }else{
            $map['title']    =   array('like', '%'.(string)$name.'%');
        }
        //查询出所有的分类
        $list = $this->lists('Brand', $map, 'sort asc');
        $list = int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '品牌管理';
        $this->display();
    }

    //品牌添加
    public function add()
    {
        $Brand = D('Brand');
        if (IS_POST) {
            if (false !== $Brand->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Brand->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->assign('info', null);
            $this->meta_title = '新增品牌';
            $this->display('edit');
        }
    }

    //品牌编辑
    public function edit($id = null)
    {
        $Brand = D('Brand');
        if (IS_POST) { //提交表单
            if (false !== $Brand->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Brand->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            /* 获取分类信息 */
            $info = $id ? $Brand->info($id) : '';
//            echo "<pre>";
//            print_r($info);
//            echo "</pre>";
            $this->assign('info', $info);
            $this->meta_title = '编辑品牌';
            $this->display('edit');

        }
    }

    /**
     * 会员状态修改
     * @author liu hui
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error('请选择要操作的数据!');
        }
        switch (strtolower($method)) {
            case 'forbidbrand':
                $this->forbid('Brand');
                break;
            case 'resumebrand':
                $this->resume('Brand');
                break;
            case 'deletebrand':
                $this->delete('Brand');
                break;
            default:
                $this->error('参数非法');
        }
    }
}

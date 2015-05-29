<?php

namespace Admin\Controller;
use Think\Controller;

class BrandController extends AdminController
{
    /**
     * 品牌管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index()
    {
        //查询出所有的分类
        $list = $this->lists('Brand');
        int_to_string($list);
        $this->assign('_list', $list);
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
        }
        $this->assign('brand');
        $this->display('edit');
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
        }
        /* 获取分类信息 */
        $info = $id ? $Brand->info($id) : '';
        $this->assign('info', $info);
        $this->assign('brand');
        $this->display('edit');

    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
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

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

}

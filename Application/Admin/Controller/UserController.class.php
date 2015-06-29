<?php

namespace Admin\Controller;

/**
 * 后台用户控制器
 * Class UserController
 * @package Admin\Controller
 */
class UserController extends AdminController
{

    /**
     * 普通用户管理首页
     */
    public function member()
    {
        $ucentermember=D('UcenterMember');
        $list = $ucentermember->userList('Member');
        int_to_string($list);
        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);
        $this->meta_title = '用户信息';
        $this->display();
    }



    /**
     * 商家用户管理首页
     */
    public function merchant()
    {
        $ucentermember=D('UcenterMember');
        $list = $ucentermember->userList('merchant');
        int_to_string($list);
        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);
        $this->meta_title = '商户信息';
        $this->display();
    }

    /**
     * 管理员用户管理首页
     */
    public function admin()
    {
        $ucentermember=D('UcenterMember');
        $list = $ucentermember->userList('admin');
        int_to_string($list);

        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);

        $this->meta_title = '管理员信息';
        $this->display();
    }


    public function editadmin()
    {

//        $ucentermember=D('UcenterMember');
//        if (IS_POST) {
//            if (false !== $ucentermember->update()) {
//                $this->success('新增成功！', U('index'));
//            } else {
//                $error = $ucentermember->getError();
//                $this->error(empty($error) ? '未知错误！' : $error);
//            }
//        } else {
//
//            $info = $id ? $ucentermember->info($id) : '';
//
//            $this->assign('info', $info);
            $this->meta_title = '编辑信息';
            $this->display();
//        }

    }

    /**
     * 用户行为列表
     * @author Liu Hui
     */
    public function action(){
        //获取列表数据
        $Action =   M('Action')->where(array('status'=>array('gt',-1)));
        $list   =   $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
       // Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     * @author Liu Hui
     */
    public function addAction(){
        $this->meta_title = '新增行为';
        $this->assign('data',null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author Liu Hui
     */
    public function editAction(){
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑行为';
        $this->display('editaction');
    }

    /**
     * 更新行为
     * @author Liu Hui
     */
    public function saveAction(){
        $res = D('Action')->update();
        if(!$res){
            $this->error(D('Action')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));
        }
    }
}
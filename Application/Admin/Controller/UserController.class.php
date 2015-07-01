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
        $ucentermember = D('UcenterMember');
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
        $ucentermember = D('UcenterMember');
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
        $ucentermember = D('UcenterMember');
        $list = $ucentermember->userList('admin');
        int_to_string($list);

        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);

        $this->meta_title = '管理员信息';
        $this->display();
    }


    public function editadmin($id)
    {

        $ucentermember = D('UcenterMember');
        if (IS_POST) {
            if (false !== $ucentermember->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $ucentermember->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            $info = $id ? $ucentermember->info($id) : '';

            $this->assign('info', $info);
            $this->meta_title = '编辑信息';
            $this->display();
        }

    }

    /**
     * 用户行为列表
     * @author Liu Hui
     */
    public function action()
    {
        //获取列表数据
        $Action = M('Action')->where(array('status' => array('gt', -1)));
        $list = $this->lists($Action);
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
    public function addAction()
    {
        $this->meta_title = '新增行为';
        $this->assign('data', null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author Liu Hui
     */
    public function editAction()
    {
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data', $data);
        $this->meta_title = '编辑行为';
        $this->display('editaction');
    }

    /**
     * 更新行为
     * @author Liu Hui
     */
    public function saveAction()
    {
        $res = D('Action')->update();
        if (!$res) {
            $this->error(D('Action')->getError());
        } else {
            $this->success($res['id'] ? '更新成功！' : '新增成功！', Cookie('__forward__'));
        }
    }


    /**
     * 修改昵称初始化
     * @author Liu Hui
     */
    public function updateNickname()
    {
        if (IS_POST) {
            //获取参数
            $nickname = I('post.nickname');
            $password = I('post.password');
            empty($nickname) && $this->error('请输入昵称');
            empty($password) && $this->error('请输入密码');

            //密码验证
            $User = new UserApi();
            $uid = $User->login(UID, $password, 4);
            ($uid == -2) && $this->error('密码不正确');

            $Member = D('Member');
            $data = $Member->create(array('nickname' => $nickname));
            if (!$data) {
                $this->error($Member->getError());
            }

            $res = $Member->where(array('uid' => $uid))->save($data);

            if ($res) {
                $user = session('user_auth');
                $user['username'] = $data['nickname'];
                session('user_auth', $user);
                session('user_auth_sign', data_auth_sign($user));
                $this->success('修改昵称成功！');
            } else {
                $this->error('修改昵称失败！');
            }
        } else {
            $nickname = M('Member')->getFieldByUid(UID, 'nickname');
            $this->assign('nickname', $nickname);
            $this->meta_title = '修改昵称';
            $this->display('updatenickname');
        }
    }

    /**
     * 修改密码初始化
     * @author Liu Hui
     */
    public function updatePassword()
    {
        if (IS_POST) {
            //获取参数
            $password = I('post.old');
            empty($password) && $this->error('请输入原密码');
            $data['password'] = I('post.password');
            empty($data['password']) && $this->error('请输入新密码');
            $repassword = I('post.repassword');
            empty($repassword) && $this->error('请输入确认密码');

            if ($data['password'] !== $repassword) {
                $this->error('您输入的新密码与确认密码不一致');
            }
            $ucentermember = D('UcenterMember');
            $res = $ucentermember->updateUserFields(UID, $password, $data);
            if (false !== $res) {
                $this->success('修改密码成功！');
            } else {
                $error = $ucentermember->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '修改密码';
            $this->display('updatepassword');
        }
    }
}
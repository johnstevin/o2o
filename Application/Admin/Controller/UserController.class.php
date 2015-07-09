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
        //int_to_string($list);
        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);
        $this->meta_title = '用户信息';
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display();
    }


    /**
     * 商家用户管理首页
     */
    public function merchant()
    {
        $ucentermember = D('UcenterMember');
        $list = $ucentermember->userList('merchant');
        //int_to_string($list);
        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);
        $this->meta_title = '商户信息';
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display();
    }

    /**
     * 管理员用户管理首页
     */
    public function admin()
    {
        $ucentermember = D('UcenterMember');
        $list = $ucentermember->userList('admin');
        // int_to_string($list);

        $this->assign('_list', $list['data']);
        $this->assign('_page', $list['_page']);

        $this->meta_title = '管理员信息';
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
        $this->display();
    }

    /**
     * 编辑管理员
     * @param null $uid
     */
    public function editadmin($uid = null)
    {

        $ucentermember = D('UcenterMember');
        if (IS_POST) {
            if (false !== $ucentermember->editInfo('admin')) {
                $this->success('编辑成功！');
            } else {
                $error = $ucentermember->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            $info = $uid ? $ucentermember->info($uid, $type = 'admin') : '';

            $this->assign('info', $info);
            $this->meta_title = '编辑信息';
            $this->display();
        }

    }

    /**
     * 编辑商家
     * @param null $uid
     */
    public function editmerchant($uid = null)
    {

        $ucentermember = D('UcenterMember');
        if (IS_POST) {
            if (false !== $ucentermember->editInfo('merchant')) {
                $this->success('编辑成功！');
            } else {
                $error = $ucentermember->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            $info = $uid ? $ucentermember->info($uid, $type = 'merchant') : '';

            $this->assign('info', $info);
            $this->meta_title = '编辑信息';
            $this->display();
        }

    }

    /**
     * 编辑用户
     * @param null $uid
     */
    public function editmember($uid = null)
    {

        $ucentermember = D('UcenterMember');
        if (IS_POST) {
            if (false !== $ucentermember->editInfo('member')) {
                $this->success('编辑成功！');
            } else {
                $error = $ucentermember->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            $info = $uid ? $ucentermember->info($uid, $type = 'member') : '';

            $this->assign('info', $info);
            $this->meta_title = '编辑信息';
            $this->display();
        }

    }


    function editProfile(){
        $ucentermember = D('UcenterMember');
        if (IS_POST) {
            if (false !== $ucentermember->editProfile()) {
                $this->success('编辑成功！');
            } else {
                $error = $ucentermember->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            $info = $ucentermember->info(UID, $type = 'admin');

            $this->assign('info', $info);
            $this->meta_title = '编辑信息';
            $this->display('editadmin');
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
        //记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

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
                $error = $this->showRegError($ucentermember->getError());
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '修改密码';
            $this->display('updatepassword');
        }
    }

    /**
     * 注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0)
    {
        switch ($code) {
            case -1:
                $error = '用户名长度必须在16个字符以内！';
                break;
            case -2:
                $error = '用户名被禁止注册！';
                break;
            case -3:
                $error = '用户名被占用！';
                break;
            case -4:
                $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5:
                $error = '邮箱格式不正确！';
                break;
            case -6:
                $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7:
                $error = '邮箱被禁止注册！';
                break;
            case -8:
                $error = '邮箱被占用！';
                break;
            case -9:
                $error = '手机格式不正确！';
                break;
            case -10:
                $error = '手机被禁止注册！';
                break;
            case -11:
                $error = '手机号被占用！';
                break;
            case -12:
                $error = '用户注册失败！code:-12';
                break;
            case -13:
                $error = '分配授权失败！code:-13';
                break;
            default:
                $error = $code;
        }
        return $error;
    }


    /**
     * 状态修改
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error('请选择要操作的数据!');
        }
        /*根据用户type判断是组织类型*/
        $_type = I('_type');
        switch (strtolower($_type)) {
            case '1':
                $model = 'Admin';
                $key='id';
                break;
            case'3':
                $model = 'Member';
                $key='uid';
                break;
            case'2':
                $model = 'Merchanter';
                $key='id';
                break;
            default:
                $this->error('参数错误');
                break;
        }

        $ucentermember = D('UcenterMember');
        if (false !== $ucentermember->changeStatus($model,$key, $method)) {
            $this->success('状态修改成功');
        } else {
            $error = $ucentermember->getError();
            $this->error(empty($error) ? '未知错误' : $error);
        }

    }
}
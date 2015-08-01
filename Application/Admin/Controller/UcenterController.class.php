<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 15-7-28
 * Time: 上午9:11
 */
namespace Admin\Controller;
use Think\Controller;

class UcenterController extends Controller{

    protected function _initialize()
    {
        // 获取当前用户ID
        if (defined('UID')) return;
        define('UID', is_admin_login());
        if (!UID) {// 还没登录 跳转到登录页面
            $this->redirect('Public/login');
        }
        $this->isOnline(UID, time());
    }
    /**
     * @param    $uid
     * @author   Stevin.John@qq.com
     */
    public function isOnline ( $uid, $ac_time ) {
        $res = S('ADMIN_ONLINE_'.$uid);
        !$res ? $this->error('超时！请重新登陆') : '';
        $res['token'] === session('admin_auth')['token'] ? : $this->error('您的账号已在其它地方登陆，请重新登陆');
        $res['last_login_time'] = $ac_time;
        S('ADMIN_ONLINE_'.$uid, $res, 1200);
    }
    /**
     *普通用户
     */
    public function index()
    {
        $UserInfo = D('UcenterMember')->info(UID,'admin');

        if($UserInfo['is_admin']==1&&$UserInfo['status']==1){
            $this->redirect('Index/index');
        }
        $this->display();
    }

    /**
     * 商家入驻
     */
    public function merchantRegister()
    {
        $UserInfo = D('UcenterMember')->info(UID,'admin');

        if($UserInfo['is_admin']==1&&$UserInfo['status']==1){
            $this->redirect('Index/index');
        }
        $this->display();
    }

    /**
     *管理员注册
     */
    public function adminRegister()
    {
        if (IS_POST) {
            $real_name = I('post.real_name');
            $key = I('post.key');

            empty($real_name) && $this->error('请输入真实姓名');

            empty($key) && $this->error('请输入授权码');

            $group_id = think_decrypt($key);

            $group = M('AuthGroup')->where(['id' => $group_id])->find();

            if (empty($group)) {
                $this->error('非法授权码');
            }

            $Ucenter = D('UcenterMember');

            $UserInfo = $Ucenter->where(['id'=>UID])->find();

            if (empty($UserInfo)) {
                $this->error('非法注册');
            }

            if($UserInfo['is_admin']==1&&$UserInfo['status']==1){
                $this->redirect('Index/index');
                $this->error('你已经是管理员了');
            }

            $info['id']=UID;

            $info['real_name'] = $real_name;

            $info['is_admin'] = 1;

            if (false !== $Ucenter->adminRegister($info,$group_id)) {

                //记录行为
                action_log('admin_register', 'ucentermember', UID, UID, 1);

                $this->success('提交成功，请耐心等待');

            } else {
                $error = $Ucenter->getError();
                $this->error(empty($error) ? '未知错误' : $error);
            };

        } else {

            $UserInfo = D('UcenterMember')->info(UID,'admin');

            if($UserInfo['is_admin']==1&&$UserInfo['status']==1){
                $this->redirect('Index/index');
            }

            $this->assign('UserInfo', $UserInfo);
            $this->display();
        }

    }
}
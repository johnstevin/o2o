<?php
// +----------------------------------------------------------------------
// | 公共调用类
// +----------------------------------------------------------------------
// | Author: stevin.john Date: 2015-5-21
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi;

class PublicController extends \Think\Controller {

    /**
     * 后台用户登录
     * @author stevin.john
     */
    public function login($username = null, $password = null, $verify = null){
        if(IS_POST){
            /* 检测验证码 TODO: */
            //if(!check_verify($verify)){
            //    $this->error('验证码输入错误！');
            //}

            $User = new UserApi;
            $uid = $User->login($username, $password, 5);
            if(0 < $uid){ //UC登录成功
                /* 登录用户 */
                $Member = D('Member');
                if($Member->login($uid)){ //登录用户
                    //TODO:跳转到登录前页面
                    $this->success('登录成功！', U('Index/index'));
                } else {
                    $this->error($Member->getError());
                }

            } else { //登录失败
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {
//            if(is_login()){
//                $this->redirect('Index/index');
//            }else{
//                /* 读取数据库中的配置 */
//                $config	=	S('DB_CONFIG_DATA');
//                if(!$config){
//                    $config	=	D('Config')->lists();
//                    S('DB_CONFIG_DATA',$config);
//                }
//                C($config); //添加配置
                
                $this->display('User/login');
            //}
        }
    }

    /**
     * 后台用户注册
     * @author stevin.john
     */
    public function register($mobile = '', $password = '', $username = '', $email = '',  $verify = ''){

        if(IS_POST){
            /* 检测验证码 */
            //if(!check_verify($verify)){
            //    $this->error('验证码输入错误！');
            //}

            /* 调用注册接口注册用户 */
            $User = new UserApi;
            $uid = $User->register($mobile, $password, $username, $email);
            if(0 < $uid){ //注册成功
                //TODO: 发送验证邮件
                $this->success('注册成功！',U('login'));
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }

        } else {
            print_r(C('HHH'));exit;
            $this->display('User/register');
        }
    }

    /* 退出登录 */
    public function logout(){
        if(is_login()){
            D('Member')->logout();
            session('[destroy]');
            $this->success('退出成功！', U('login'));
        } else {
            $this->redirect('login');
        }
    }

    public function verify(){
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

}

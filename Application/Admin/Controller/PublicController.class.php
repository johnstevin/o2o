<?php
// +----------------------------------------------------------------------
// | 公共调用类
// +----------------------------------------------------------------------
// | Author: stevin.john Date: 2015-5-21
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Think\Controller;

class PublicController extends Controller {

    /* 保存允许访问的公共方法 */
    //static protected $allow = array('login','register');

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
            $Ucenter = D('UcenterMember');
            $uid = $Ucenter->login($username, $password, 5);
            if(0 < $uid){

                $this->success('登录成功！', U('Index/index'));

            } else {
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    case -3: $error = '插入或更新管理员信息失败'; break;
                    default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {
            if(is_admin_login()){
                $this->redirect('Index/index');
            }else{
//                /* 读取数据库中的配置 */
//                $config	=	S('DB_CONFIG_DATA');
//                if(!$config){
//                    $config	=	D('Config')->lists();
//                    S('DB_CONFIG_DATA',$config);
//                }
//                C($config); //添加配置
                
                $this->display('User/login');
            }
        }
    }

    /**
     * 后台用户注册
     * @author stevin.john
     */
    public function register(){

        if(IS_POST){
            $verify      = I('post.verify');
            $mobile      = I('post.mobile');
            $password    = I('post.password');
            $username    = I('post.username');
            $email       = I('post.email');
            $group_id    = I('post.group_id');
            $is_admin    = I('post.is_admin',1);

            /* 检测验证码 */
            //if(!check_verify($verify)){
            //    $this->error('验证码输入错误！');
            //}

            $Ucenter = D('UcenterMember');
            D()->startTrans();

            $uid = $Ucenter->register($mobile, $password, $username, $email);
            if(0 < $uid){
                //赋组织：用户组，赋角色：普通用户,状态1-正常
                //赋组织：GET组织id,赋角色：0,状态0-待审核
                $auth = D('AuthAccess');
                $data[] = array(
                    'uid'          => $uid,
                    'group_id'     => C('AUTH_GROUP_ID.CLIENT_GROUP_ID'),
                    'role_id'      => C('AUTH_ROLE_ID.CLIENT_ROLE_ID'),
                    'status'       => 1,
                );
                $data[] = array(
                    'uid'          => $uid,
                    'group_id'     => $group_id,
                    'role_id'      => 0,
                    'status'       => 0,
                );
                $result = $auth->addUserAccess($data);
                if( 0 > $result ){
                    D()->rollback();
                    $this->error($this->showRegError($result));
                }else{
                    D()->commit();
                    $this->success('注册成功！', U('login'));
                }

            } else {
                D()->rollback();
                $this->error($this->showRegError($uid));
            }

        } else {
            $this->display('User/register');
        }
    }

    /**
     * 注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在16个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            case -12: $error = '用户注册失败！code:-12'; break;
            case -13: $error = '分配授权失败！code:-13'; break;
            default:  $error = '未知错误';
        }
        return $error;
    }

    /**
     * 退出登陆
     */
    public function logout(){
        if(is_admin_login()){
            D('UcenterMember')->logout();
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

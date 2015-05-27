<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimerchant\Controller;

/**
 * 商户用户
 * Class UserController
 * @package Api\Controller
 */
class UserController extends ApiController {

    /**
     * 商户登陆
     * @param
     * @author  stevin
     */
    public function login(){

    }

    /**
     * 商户注册
     * @param
     * @author  stevin
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
     * 商户提交资料
     * @param
     * @author  stevin
     */
    public function submitInfo(){

    }

    /**
     * 商户个人资料
     * @param
     * @author  stevin
     */
    public function userInfo(){

    }

    /**
     * 商户个人资料修改
     * @param
     * @author  stevin
     */
    public function updateInfo(){

    }

    /**
     * 商户销售额统计
     * @param
     * @author  stevin
     */
    public function countSales(){

    }

    /**
     * 商铺资料信息
     * @param
     * @author  stevin
     */
    public function merchantShopInfo(){

    }

    /**
     * 商铺资料信息修改
     * @param
     * @author  stevin
     */
    public function merchantShopUpdate(){

    }

}
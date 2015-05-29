<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;

/**
 * 用户中心
 * Class UserController
 * @package Api\Controller
 */
class UserController extends ApiController {

    /**
     * 用户登陆
     * @param
     * @author  stevin
     */
    public function login(){
        if(IS_POST){
            $username = I('post.username');
            $password = I('post.password');

            $Ucenter  = D('UcenterMember');
            $uid = $Ucenter->login($username, $password, 5);
            if(0 < $uid){

                $this->apiSuccess('登录成功！');

            } else {
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    case -3: $error = '插入或更新管理员信息失败'; break;
                    default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->apiError(40012, $error);
            }

        }else{
            if(is_member_login()){
                $this->redirect('Index/index');
            }else{
                $this->display('User/login');
            }
        }
    }

    /**
     * 用户注册
     * @param
     * @author  stevin
     */
    public function register(){
        if(IS_POST){
            $mobile     = I('post.mobile');
            $password   = I('post.password');

            $Ucenter = D('UcenterMember');
            D()->startTrans();

            $uid = $Ucenter->register($mobile, $password);
            if(0 < $uid){
                $auth = D('AuthAccess');
                $data[] = array(
                    'uid'          => $uid,
                    'group_id'     => C('AUTH_GROUP_ID.CLIENT_GROUP_ID'),
                    'role_id'      => C('AUTH_ROLE_ID.CLIENT_ROLE_ID'),
                    'status'       => 1,
                );
                $result = $auth->addUserAccess($data);

                if( 0 > $result ){
                    D()->rollback();
                    $this->apiError(40013,$this->showRegError($result));
                }else{
                    D()->commit();
                    $this->apiSuccess('注册成功！', null, null);
                }

            } else {
                D()->rollback();
                $this->apiError(40014,$this->showRegError($uid));
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
        if(is_member_login()){
            D('UcenterMember')->logout();
            session('[destroy]');
            $this->apiSuccess('退出成功！');
        }
    }

    /**
     * 用户个人资料
     * @param
     * @author  stevin
     */
    public function getUserInfo(){

    }

    /**
     * 用户个人修改
     * @param
     * @author  stevin
     */
    public function getUserEdit(){

    }

    /**
     * 用户订单列表
     * @param
     * @author  stevin
     */
    public function getOrderList(){

    }

    /**
     * 用户订单详情
     * @param
     * @author  stevin
     */
    public function getOrderDetail(){

    }

    /**
     * 用户订单删除
     * @param
     * @author  stevin
     */
    public function getOrderDel(){

    }

    /**
     * 用户常用地址
     * @param
     * @author  stevin
     */
    public function getAddressList(){

    }

    /**
     * 用户地址添加
     * @param
     * @author  stevin
     */
    public function getAddressAdd(){

    }

    /**
     * 用户地址修改
     * @param
     * @author  stevin
     */
    public function getAddressEdit(){

    }

    /**
     * 用户地址删除
     * @param
     * @author  stevin
     */
    public function getAddressDel(){

    }


}
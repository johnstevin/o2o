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
     * 用户登陆,参数用POST提交
     * @param string username 用户名称
     * @param string password 密码
     * @author  stevin,WangJiang
     * @return json
     * {
     *  "token":"<access token 随后某些调用需要>"
     * }
     */
    public function login(){
        try{
            if(IS_POST){
                $username = I('post.username');
                $password = I('post.password');

                $Ucenter  = D('UcenterMember');
                $token = $Ucenter->login($username, $password, 5);
                if(0 < $token){
                    $this->apiSuccess(['token'=>$token]);
                } else {
                    switch($token) {
                        case 0:$error = '参数错误！'; break; //系统级别禁用
                        case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                        case -2: $error = '密码错误！'; break;
                        case -3: $error = '插入或更新管理员信息失败'; break;
                        default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                    }
                    E($error);
                }
            }else
                E('非法调用');
        }catch (\Exception $ex){
            $this->apiError(40012, $ex->getMessage());
        }
    }

    /**
     * 用户注册,参数用POST提交
     * @param string mobile   手机号
     * @param string password 密码
     * @author  stevin,WangJiang
     * @return json
     */
    public function register(){
        try{
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
                        'group_id'     => C('AUTH_GROUP_ID.GROUP_ID_MEMBER'),
                        'role_id'      => C('AUTH_ROLE_ID.ROLE_ID_MEMBER_CLIENT'),
                        'status'       => 1,
                    );
                    $result = $auth->addUserAccess($data);

                    if( 0 > $result ){
                        D()->rollback();
                        $this->apiError(40013,$this->showRegError($result));
                    }else{
                        D()->commit();
                        $this->apiSuccess(null,'注册成功！');
                    }

                } else {
                    D()->rollback();
                    $this->apiError(40014,$this->showRegError($uid));
                }

            } else {
                $this->display('User/register');
            }
        }catch (\Exception $ex){
            $this->apiError(50112,$ex->getMessage());
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
     * @author WangJiang
     * @param string accesstoken 调用令牌
     * @return json
     */
    public function logout(){
        D('UcenterMember')->logout($this->getToken());
        //session('[destroy]');
        $this->apiSuccess(null,'退出成功！');
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
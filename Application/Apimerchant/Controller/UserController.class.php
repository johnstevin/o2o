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
     * <pre>
     * 商户登陆,参数用POST提交
     * string username 用户名称
     * string password 密码
     * </pre>
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
     * <pre>
     * 商户注册,参数用POST提交
     * string mobile   手机号
     * string password 密码
     * </pre>
     * @author  stevin,WangJiang
     * @return json
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
                    'group_id'     => C('AUTH_GROUP_ID.GROUP_ID_MERCHANT'),
                    'role_id'      => C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_COMMITINFO'),
                    'status'       => 1,
                );
                $result = $auth->addUserAccess($data);

                if( 0 > $result ){
                    D()->rollback();
                    $this->apiError(40010,$this->showRegError($result));
                }else{
                    D()->commit();
                    $this->apiSuccess(null,'注册成功！');
                }

            } else {
                D()->rollback();
                $this->apiError(40009,$this->showRegError($uid));
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
     * <pre>
     * 退出登陆
     * string accesstoken 调用令牌
     * </pre>
     * @author WangJiang
     * @return json
     */
    public function logout(){
        D('UcenterMember')->logout($this->getToken());
        //session('[destroy]');
        $this->apiSuccess(null,'退出成功！');
    }

    /**
     * @ignore
     * 商户提交资料
     * @param
     * @author  stevin
     */
    public function submitInfo(){

    }

    /**
     * @ignore
     * 商户个人资料
     * @param
     * @author  stevin
     */
    public function userInfo(){

    }

    /**
     * @ignore
     * 商户个人资料修改
     * @param
     * @author  stevin
     */
    public function updateInfo(){

    }

    /**
     * @ignore
     * 商户销售额统计
     * @param
     * @author  stevin
     */
    public function countSales(){

    }

    /**
     * @ignore
     * 商铺资料信息
     * @param
     * @author  stevin
     */
    public function merchantShopInfo(){

    }

    /**
     * @ignore
     * 商铺资料信息修改
     * @param
     * @author  stevin
     */
    public function merchantShopUpdate(){

    }

}
<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimerchant\Controller;
use Common\Model\MerchantShopModel;
use Common\Model\MerchantModel;

require __ROOT__.'Addons/Sms/Common/function.php';

/**
 * 商户用户
 * Class UserController
 * @package Api\Controller
 */
class UserController extends ApiController {


    const CODE_EXPIRE=10;

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
                    $this->apiSuccess(['data'=>['token'=>$token]]);
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
            $code = I('post.code');

            if(verify_sms_code($mobile,$code))
                E("验证码错误或已过期，请重新获取");

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

    public function getVerifyCode($mobile){
        try{
            $this->apiSuccess(['data'=>send_sms_code($mobile)]);
        }catch (\Exception $ex){
            $this->apiError(40009,$ex->getMessage());
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
    public function userInfos( $mapUid, $field='*' ){
        $model = D("Merchant");
        $result = $model->getInfos($mapUid,$field);
        return $result;


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

    /**
     * 获取员工注册Url
     * @param    : inter $shop_id
     * @author   : Stevin.John@qq.com
     */
    public function getRegisterUrl(){
        $shop_id  = is_numeric(I('get.shop_id')) ? I('get.shop_id') : 0;
        if($shop_id==0)
            $this->apiError('40020', '非法操作');
        $model = D('MerchantShop');
        $result = $model->get($shop_id, 'id,group_id,staff_register_url');
        if(empty($result))
            $this->apiError('40021', '找不到此店铺');
        if( $result['staff_register_url'] != null ){
            $this->apiSuccess(array('data'=>$result['staff_register_url']),'获取Url成功');
        }else{
            //生成url
            $this->apiSuccess(array('data'=>'apimchant.php?s=User/staffAdd/shop_id/' . $shop_id),'生成Url成功');

        }


    }

    /**
     * 员工注册
     * @Url    : /Apimerchant/User/staffAdd/shop_id/*
     * @param  : inter $shop_id  店铺id
     * @author : Stevin.John@qq.com
     */
    public function staffAdd(){
        if( IS_POST ){
            $shop_id  = is_numeric(I('post.shop_id')) ? I('post.shop_id') : 0;
            if($shop_id==0)
                $this->apiError('40030', '非法操作');
            $model = D('MerchantShop');
            $result = $model->get($shop_id, 'id,group_id,type');
            if(empty($result))
                $this->apiError('40031', '找不到此店铺');
            $group_id  = $result['group_id'];
            switch( $result['type'] ){
                case 1 : $role_id = C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_STAFF');     break;
                case 2 : $role_id = C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_WORKER'); break;
                default : $this->apiError('40032', '店铺类型错误');
            }


            //TODO 这里注册可以写个公共调用
            // Start
            $mobile     = I('post.mobile');
            $password   = I('post.password');

            $Ucenter = D('UcenterMember');
            D()->startTrans();

            $uid = $Ucenter->register($mobile, $password);
            if(0 < $uid){
                $auth = D('AuthAccess');
                $data[] = array(
                    'uid'          => $uid,
                    'group_id'     => C('AUTH_GROUP_ID.GROUP_ID_MEMBER_CLIENT'),
                    'role_id'      => C('AUTH_ROLE_ID.ROLE_ID_MEMBER_CLIENT'),
                    'status'       => 1,
                );
                $data[] = array(
                    'uid'          => $uid,
                    'group_id'     => $group_id,
                    'role_id'      => $role_id,
                    'status'       => $auth::AUTH_STATUS_AWAIT,
                );
                $result = $auth->addUserAccess($data);

                if( 0 > $result ){
                    D()->rollback();
                    $this->apiError(40033,$this->showRegError($result));
                }else{
                    D()->commit();
                    $this->apiSuccess('注册成功！', null, null);
                }

            } else {
                D()->rollback();
                $this->apiError(40034,$this->showRegError($uid));
            }
            // End




        } else {

            $this->display();

        }


    }

    /**
     * 员工管理
     * @author : Stevin.John@qq.com
     */
    public function staffManage(){
        $shop_id  = is_numeric(I('get.shop_id')) ? I('get.shop_id') : 0;
        if($shop_id==0)
            $this->apiError('40030', '非法操作');
        $model = D('MerchantShop');
        $result = $model->get($shop_id, 'id,group_id,type');
        if(empty($result))
            $this->apiError('40031', '找不到此店铺');
        $group_id  = $result['group_id'];
        switch( $result['type'] ){
            case 1 : $role_id = C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_STAFF');     break;
            case 2 : $role_id = C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_WORKER'); break;
            default : $this->apiError('40032', '店铺类型错误');
        }

        $auth = D('AuthAccess');
        $map = array(
            'group_id'   => $group_id,
            'role_id'    => $role_id,
        );
        $field = 'uid';
        $uids = $auth->get( $map,$field );
        if($uids == -1)
            $this->apiError('40033', '获取员工失败');
        $uids = implode(',',$uids);
        $mapUcenter  = array('in',$uids);
        $fieldUcenter      = 'a.id,a.mobile,a.username,a.email,a.reg_time,b.status,b.last_login_ip,b.last_login_time';
        $resultUserInfos = $this->userInfos($mapUcenter,$fieldUcenter);
        $this->apiSuccess(array('data'=>$resultUserInfos),'获取员工成功');


    }


}
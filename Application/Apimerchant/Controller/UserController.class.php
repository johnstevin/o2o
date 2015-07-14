<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimerchant\Controller;
use Common\Model\MerchantShopModel;
use Common\Model\MerchantModel;

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
            if(true){
                //46f94c8de14fb36680850768ff1b7f2a  123qwe
                //e10adc3949ba59abbe56e057f20f883e  123456
                $username = I('username');
                $password = I('password');
                //$registrationId = I('registrationId') == '' ? E('注册码不能为空') : I('registrationId');
                $registrationId = '';
                //print_r('aaa');exit;
                $Ucenter  = D('UcenterMember');
                $token = $Ucenter->login($username, $password, $registrationId,5);
                if(0 < $token){
                    $this->apiSuccess(array('data'=>array('token'=>$token,'auth'=>F('User/Login/merchant_auth' . $token))), '登陆成功');
                } else {
                    switch($token) {
                        case 0:$error = '参数错误！'; break; //系统级别禁用
                        case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                        case -2: $error = '密码错误！'; break;
                        case -3: $error = '插入或更新管理员信息失败'; break;
                        case -7: $error = '获取权限失败'; break;
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

            if(!verify_sms_code($mobile,$code))
                $this->apiError(40009,$this->showRegError(-14));

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
                $data[] = array(
                    'uid'          => $uid,
                    'group_id'     => C('AUTH_GROUP_ID.GROUP_ID_MEMBER_CLIENT'),
                    'role_id'      => C('AUTH_ROLE_ID.ROLE_ID_MEMBER_CLIENT'),
                    'status'       => 1,
                );
                $result = $auth->addUserAccess($data);

                if( 0 > $result ){
                    D()->rollback();
                    $this->apiError(40010,$this->showRegError($result));
                }else{
                    D()->commit();
                    $this->apiSuccess(array('data'=>''),'注册成功！');
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
     * 获得验证码
     * @author WangJiang
     * @param $mobile
     * @return json
     */
    public function getVerifyCode($mobile){
        try{
            send_sms_code($mobile);
            $this->apiSuccess(['data'=>''],'');
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
            case -4:  $error = '密码长度不够！'; break;
            case -7:  $error = '获取权限失败！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            case -12: $error = '用户注册失败！code:-12'; break;
            case -13: $error = '分配授权失败！code:-13'; break;
            case -14: $error = '验证码错误或已过期，请重新获取'; break;
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
     * 用户个人修改
     * @param
     * @author  Stevin.John@qq.com
     * @Url
     */
    public function editInfo(){
        try {
            $uid = $this->getUserId();
            $type = I('get.type');
            switch ( $type ) {
                case 'password' :
                    $model = D("UcenterMember");
                    $opassword = I('post.opassword');
                    $npassword = I('post.npassword');
                    if ($opassword === $npassword)
                        E('新旧密码不能相同');

                    $user = $model->where(array('id'=>$uid))->find();
                    if(is_array($user) && $user['is_member']){
                        if(generate_password($opassword, $user['saltkey']) !== $user['password'])
                            E('旧密码错误'); //密码错误

                    } else {
                        E('用户不存在或不是用户'); //用户不存在或不是管理员
                    }
                    //46f94c8de14fb36680850768ff1b7f2a  123qwe
                    //e10adc3949ba59abbe56e057f20f883e  123456
                    $data['password'] = $npassword;
                    $data['id'] = $uid;
                    break;
                case 'mobile' :
                    //TODO
                    $step = is_numeric(I('get.step')) && I('get.step') != '' ? I('get.step') : E('step不能空或不是数字');
                    $model = D("UcenterMember");
                    $user    = $model->where(array('id'=>$uid))->find();
                    empty($user) ? E('非法用户操作') : '';
                    switch ( $step ) {
                        case 1 :
                            $oMobile = $user['mobile'];
                            $oCode   = I('post.code');
                            if(!verify_sms_code($oMobile, $oCode))
                                E('验证码错误');
                            break;
                        case 2 :
                            $nMobile = I('post.mobile');
                            $nCode   = I('post.code');
                            if(!verify_sms_code($nMobile, $nCode))
                                E('验证码错误');
                            $data['mobile'] = $nMobile;
                            $data['id'] = $uid;
                            break;
                        default :
                            E('错误操作');
                    }
                    break;
                default :
                    E('必须传递type参数');
            }


            $result = $model->saveInfo($data);
            if($result===true){
                $this->apiSuccess(array('data'=>''), '成功');
            }else{

                E($model->getError());

            }


        } catch (\Exception $ex) {
            $this->apiError(50115, $ex->getMessage());
        }
    }

    /**
     * 忘记密码
     * @errorCode    50116
     * @author       Stevin.John@qq.com
     */
    public function forgetPassword(){
        try {
            $step   = I('get.step');
            switch ( $step ) {
                case 1 :
                    $mobile = I('post.mobile');
                    $code   = I('post.code');
                    verify_sms_code($mobile,$code) ? '' : E('验证码错误或已过期，请重新获取');

                    $rules = array(
                        array('mobile', '#^13[\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', '手机格式不正确'), //手机格式不正确
                        array('mobile', 'checkDenyMobile', '您的手机号禁止注册', 0, 'callback'), //过滤手机黑名单
                    );
                    $model  = M("UcenterMember");
                    //TODO 这里做手机认证
                    $map = array(
                        'is_merchant' => 1,
                        'mobile'      => $mobile,
                    );
                    $model->field('id')->where($map)->find() ? '' : E('该手机号未注册或不是商家用户');
                    $randVal = generate_saltKey();
                    S('_Merchant_User_ForgetPwd_randVal_'.$mobile, $randVal, 300);
                    $this->apiSuccess(array('data'=>array('randVal'=>$randVal)), '请点下一步');

                    break;
                case 2 :
                    //TODO 这里要做验证
                    $mobile    = I('post.mobile') != '' ? I('post.mobile') :   E('请设置手机号');
                    $password  = I('post.password') != '' ? I('post.password') : E('请设置密码');
                    $randVal   = I('post.randval') != '' ? I('post.randval') :  E('不安全的密码设置');
                    // TODO : 这里涉及到如果设置缓存前缀无法读取的问题，后期解决
                    $randCache = S('_Merchant_User_ForgetPwd_randVal_'.$mobile);
                    $randCache !== false ? '' : E('已超时，请重新设置');
                    $randCache == $randVal ? '' : E('安全码不正确');

                    $model  = D("UcenterMember");
                    //TODO 这里做手机认证
                    $map = array(
                        'is_merchant' => 1,
                        'mobile'      => $mobile,
                    );
                    $uid = $model->field('id')->where($map)->find() ? : E('该手机号未注册或不是商家用户');
                    $data = array(
                        'id'           => $uid['id'],
                        'password'     => $password,
                    );
                    $model->saveInfo($data) === true ? $this->apiSuccess(array('data'=>''), '密码找回成功') : E($model->getError());
                    break;
                default :
                    E('请设置正确的step');
            }

        } catch (\Exception $ex) {
            $this->apiError(50116, $ex->getMessage());
        }
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
        $this->getUserId();
        $shop_id  = is_numeric(I('get.shop_id')) ? I('get.shop_id') : 0;
        if($shop_id==0)
            $this->apiError('40020', '非法操作');
        $model = D('MerchantShop');
        $result = $model->get($shop_id, 'id,group_id,title');
        if(empty($result))
            $this->apiError('40021', '找不到此店铺');
//        if( $result['staff_register_url'] != null ){
//            $this->apiSuccess(array('data'=>$result['staff_register_url'].'/shop_name/'.$result['title']),'获取Url成功');
//        }else{
            //生成url
            $this->apiSuccess(array('data'=>'apimchant.php?s=User/staffAdd/key/' . think_encrypt($shop_id)),'生成Url成功');

//        }


    }

    /**
     * 员工注册
     * @Url    : /Apimerchant/User/staffAdd/shop_id/*
     * @param  : inter $shop_id  店铺id
     * @author : Stevin.John@qq.com
     */
    public function staffAdd(){
        if( IS_POST ){
            /* 检测验证码 */
            $mobile     = I('post.mobile');
            $verify_code=I('verify_code');
            if(!$verify_code||!verify_sms_code($mobile,$verify_code)){
                $this->apiError('40029','验证码输入错误！');
            }


            $password   = I('post.password');
            $repassword   = I('post.repassword');
            if($password!== $repassword){
                $this->apiError('40029','您输入的密码与确认密码不一致');
            }



            $shop =think_decrypt(I('post.shop_id'));
            $shop_id  = is_numeric($shop) ? $shop : 0;
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

            $real_name   = I('post.real_name');

            $Ucenter = D('UcenterMember');
            D()->startTrans();

            $uid = $Ucenter->register($mobile, $password,$real_name);
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
        $this->getUserId();
        $shop_id  = is_numeric(I('get.shop_id')) ? I('get.shop_id') : 0;
        if($shop_id==0)
            $this->apiError('40030', '非法操作');
        $model = D('MerchantShop');
        $result = $model->get($shop_id);
        if(empty($result))
            $this->apiError('40031', '找不到此店铺');
        $group_id  = $result['group_id'];
        switch($result['type']){
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
        $uids = $auth->get($map,$field);
        if($uids == -1)
            $this->apiError('40033', '获取员工失败');
        $uids = implode(',',$uids);
        $mapUcenter  = array('in',$uids);
        $fieldUcenter      = 'a.id,a.mobile,a.real_name,a.username,a.email,a.reg_time,b.status,b.last_login_ip,b.last_login_time';
        $resultUserInfos = $this->userInfos($mapUcenter,$fieldUcenter);
        $this->apiSuccess(array('data'=>$resultUserInfos),'获取员工成功');


    }


}
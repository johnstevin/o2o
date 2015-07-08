<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;

use Common\Model\UcenterMemberModel;
use Common\Model\MemberModel;
/**
 * 用户中心
 * Class UserController
 * @package Api\Controller
 */
class UserController extends ApiController {

    /**
     * <pre>
     * 用户登陆,参数用POST提交
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
                $username = I('post.username') == '' ? E('手机号不能为空') : I('post.username');
                $password = I('post.password') == '' ? E('密码不能为空') : I('post.password');
                $registrationId = I('post.registrationId') == '' ? E('注册码不能为空') : I('post.registrationId');

                $Ucenter  = D('UcenterMember');
                $token = $Ucenter->login($username, $password, $registrationId, 5);
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
     * 用户注册,参数用POST提交
     * string mobile   手机号
     * string password 密码
     * </pre>
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
     * 用户个人资料
     * @param
     * @author  stevin
     */
    public function getUserInfo(){
        try {
            $map = $this->getUserId();
            $fields = 'a.id,a.mobile,a.username,a.email,a.reg_time,a.reg_ip,b.nickname,b.sex,b.birthday,b.qq,c.path as photo,real_name';
            $uInfo = D('Member')->getMemberInfos($map, $fields);
            if(empty($uInfo))
                $this->apiError(40015,'没有此用户');
            $this->apiSuccess(array('data'=>$uInfo), '成功');
        } catch (\Exception $ex) {
            $this->apiError(50115, $ex->getMessage());
        }
    }

    /**
     * @ignore
     * 用户个人修改
     * @param
     * @author  Stevin.John@qq.com
     * @Url
     */
    public function editInfo($type=null){
        try {
            $uid = $this->getUserId();
            switch(strtolower($type)) {
                case 'photo' :
                    $ptype= 'UCENTER_MEMBER';
                    $info=upload_picture($uid,$ptype);
                    $model = D("UcenterMember");
                    $data['id'] = $uid;
                    $data['photo']=$info['filedata']['id'];
                    break;
                case 'real_name' :
                    $model = D("UcenterMember");
                    $real_name = I('post.real_name');
                    $data['real_name'] = $real_name;
                    $data['id'] = $uid;
                    break;
                case 'nickname' :
                    $model = D("Member");
                    $nickname = I('post.nickname');
                    $data['nickname'] = $nickname;
                    $data['uid'] = $uid;
                    break;
                case 'email' :
                    $model = D("UcenterMember");
                    $email = I('post.email');
                    $data['email'] = $email;
                    $data['id'] = $uid;
                    break;
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
     * @ignore
     * 用户订单列表
     * @param
     * @author  stevin
     */
    public function getOrderList(){

    }

    /**
     * @ignore
     * 用户订单详情
     * @param
     * @author  stevin
     */
    public function getOrderDetail(){

    }

    /**
     * @ignore
     * 用户订单删除
     * @param
     * @author  stevin
     */
    public function getOrderDel(){

    }

    /**
     * @ignore
     * 用户常用地址
     * @param
     * @author  stevin
     */
    public function getAddressList(){

    }

    /**
     * @ignore
     * 用户地址添加
     * @param
     * @author  stevin
     */
    public function getAddressAdd(){

    }

    /**
     * @ignore
     * 用户地址修改
     * @param
     * @author  stevin
     */
    public function getAddressEdit(){

    }

    /**
     * @ignore
     * 用户地址删除
     * @param
     * @author  stevin
     */
    public function getAddressDel(){

    }


}
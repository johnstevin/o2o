<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Model;
use Think\Model\AdvModel;

DEFINE('SALTKEY',generate_saltKey());

class UcenterMemberModel extends AdvModel {

    const USER_ADMIN         = 'admin';
    const USER_MERCHANT      = 'merchant';
    const USER_MEMBER        = 'member';

    /* 用户模型自动验证 */
    protected $_validate = array(

        /* 验证密码 */
        array('password', '6,32', -4, self::EXISTS_VALIDATE, 'length'), //密码长度不合法

        /* 验证手机号码 */
        array('mobile', '#^13[\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', -9, self::EXISTS_VALIDATE), //手机格式不正确 TODO:
        array('mobile', 'checkDenyMobile', -10, self::EXISTS_VALIDATE, 'callback'), //过滤手机黑名单
        array('mobile', '', -11, self::EXISTS_VALIDATE, 'unique'), //手机号被占用
    );

//    protected $_filter = array(
//        'password'=>array('contentWriteFilter','contentReadFilter'),
//    );

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('password', 'getPwd', self::MODEL_INSERT, 'callback'),
        array('saltkey', SALTKEY),
        array('reg_time', NOW_TIME, self::MODEL_INSERT),
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('update_time', NOW_TIME),
        array('is_member', 1),
    );

    public final function getPwd( $pwd ){
        return generate_password( $pwd, SALTKEY);
    }

    /**
     * 注册一个新用户
     * @param  string $username 用户名
     * @param  string $password 用户密码
     * @param  string $email    用户邮箱
     * @param  string $mobile   用户手机号码
     * @return integer          注册成功-用户信息，注册失败-错误编号
     */
    public function register($mobile, $password){
        $data = array(
            'password' => $password,
            'mobile'   => $mobile,
        );

        /* 添加用户 */
        if($this->create($data)){
            $uid = $this->add();
            return $uid ? $uid : -12; //-12-注册失败，大于0-注册成功
        } else {
            return $this->getError();
        }
    }

    /**
     * 检测手机是不是被禁止注册
     * @param  string $mobile 手机
     * @return boolean        ture - 未禁用，false - 禁止注册
     */
    protected function checkDenyMobile($mobile){
        return true;
    }

    /**
     * 商户登录认证
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID,5-全部）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     * @author stevin.john
     */
    public function login($username, $password, $type = 1){
        $map = array();
        switch ($type) {
            case 1:
                $map['username'] = $username;
                break;
            case 2:
                $map['email'] = $username;
                break;
            case 3:
                $map['mobile'] = $username;
                break;
            case 4:
                $map['id'] = $username;
                break;
            case 5:
                $map['username'] = $username;
                $map['email']    = $username;
                $map['mobile']   = $username;
                $map['_logic']   = 'OR';
                break;
            default:
                return 0; //参数错误
        }

        /* 获取用户数据 */
        $user = $this->where($map)->find();
        if(is_array($user) && $user['is_member']){
            /* 验证用户密码 */
            if(generate_password($password, $user['saltkey']) === $user['password']){
                //是管理员，插入或更新数据admin
                return $this->updateLogin($user); //登录成功，返回用户ID
            } else {
                return -2; //密码错误
            }
        } else {
            return -1; //用户不存在或不是管理员
        }
    }

    /**
     * 更新或插入商户登录信息
     * @param  integer $uid 用户ID
     * @author  stevin.john
     */
    protected function updateLogin($user){
        $data = array(
            'uid'              => $user['id'],
            'login'           => array('exp', '`login`+1'),
            'last_login_time' => NOW_TIME,
            'last_login_ip'   => get_client_ip(1),
            'status'          => 1,
        );
        $member = M(self::USER_MEMBER);
        $result = $member->where('uid='.$user['id'])->field('uid')->find();
        if( $result ){
            $member->save($data);
        }else{
            $member->add($data);
        }

        if ($member->getDbError())
            return -3;  //插入或更新管理员信息失败

        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid'             => $user['id'],
            'mobile'          => $user['mobile'],
            'last_login_time' => $data['last_login_time'],
        );

        session('member_auth', $auth);
        session('member_auth_sign', data_auth_sign($auth));

        return $user['id'];
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        session('member_auth', null);
        session('member_auth_sign', null);
    }


}
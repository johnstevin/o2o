<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimerchant\Model;
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
       // array('mobile', '#^13[\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', -9, self::EXISTS_VALIDATE), //手机格式不正确 TODO:
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
        array('is_merchant', 1),
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
    public function register($mobile, $password,$real_name){
        $data = array(
            'password' => $password,
            'mobile'   => $mobile,
            'real_name'   => $real_name,
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
    public function login($username, $password,$registrationId, $random, $type = 1){
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
        if(is_array($user) && $user['is_merchant']){
            /* 验证用户密码 */
            if(generate_password($password, $user['saltkey']) === $user['password']){
                /* 极光推送服务 */
                update_device_tag_alias('STORE',$registrationId, $user['id']);
                $user['random']         = $random;
                $user['registrationId'] = $registrationId;
                return $this->updateLogin($user);
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
            'id'              => $user['id'],
            'login'           => array('exp', '`login`+1'),
            'last_login_time' => NOW_TIME,
            'last_login_ip'   => get_client_ip(1),
            'status'          => 1,
        );
        $admin = M(self::USER_MERCHANT);
        $result = $admin->field('id')->where('id='.$user['id'])->find();
        if( $result ){
            $admin->save($data);
        }else{
            $admin->add($data);
        }

        if ($admin->getDbError())
            return -3;  //插入或更新管理员信息失败

        $group_tree  =  $this->loginAccess($user['id']);
        $auth = array(
            'uid'             => $user['id'],
            'mobile'          => $user['mobile'],
            'last_login_time' => $data['last_login_time'],
            'group_tree'      => $group_tree,
            'random'          => $user['random'],
            'unique'          => create_unique(),
            'ac_time'         => time(),
            'registrationId'  => $user['registrationId'],
        );
        $token = md5($auth['random'] . $auth['unique']);
        set_merchant_login($token,$auth);
        return $token;
    }

    protected function loginAccess ( $uid ) {
        /* 是否有总店未审核 */
//        $shopFields = 'id,group_id,message,status';
//        $shopMaps   = array('status'=>array('in','2,3'),'add_uid'=>$uid);
//        $shopModel  = M('MerchantShop');
//        $shopRes    = $shopModel->field($shopFields)->where($shopMaps)->find();
//
//        if (empty($shopRes)) {
//            /* 获取组织关系 */
//            //$acMap    = array('uid'=>$user['id'],'status'=>1,'group_id'=>C('AUTH_GROUP_ID.GROUP_ID_MERCHANT'));
//            $acMap    = array('uid'=>$uid,'status'=>1);
//            $acFields = 'uid,group_id,role_id';
//            $acRes    = D("AuthAccess")->lists($acMap, $acFields);
//            if ( $acRes === false )
//                return -7;  //获取权限失败
//            $acCount  = count($acRes);
//            // TODO 这里有问题
//            if ( $acCount > 2) {
//                $group_tree = array();
//            } else {
//                $group_tree = array(
//                    'group_id'    => 0,
//                    'role_id'     => _merchant_roleName($acRes[0]['role_id']),
//                    'shop_id'     => 0,
//                    'shop_status' => 0,
//                    'shop_message'=> '',
//                );
//            }
//
//
//
//
//        } else {
//            $group_tree = array(
//                'group_id'    => $shopRes['group_id'],
//                'role_id'     => _merchant_roleName(C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_COMMITINFO')),
//                'shop_id'     => $shopRes['id'],
//                'shop_status' => $shopRes['status'],
//                'shop_message'=> is_null($shopRes['message']) ? '' : $shopRes['message'],
//            );
//        }

        /* 2015-7-14 改进 */
        $role1    = C('AUTH_GROUP_ID.GROUP_ID_MERCHANT').        ':'.C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_COMMITINFO');
        $role2    = C('AUTH_GROUP_ID.GROUP_ID_MERCHANT_SHOP').   ':'.C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER');
        $role3    = C('AUTH_GROUP_ID.GROUP_ID_MERCHANT_SHOP').   ':'.C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_STAFF');
        $role4    = C('AUTH_GROUP_ID.GROUP_ID_MERCHANT_VEHICLE').':'.C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');
        $role5    = C('AUTH_GROUP_ID.GROUP_ID_MERCHANT_VEHICLE').':'.C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_WORKER');

        $acFields = 'a.uid,a.group_id,a.role_id,g.level,g.title';
        $acRes    = D("AuthAccess")->lists($acFields, C('AUTH_GROUP_TYPE.MERCHANT'), $uid, 0, 0, 1);
        if(empty($acRes)) return 'Failed to get permission!';
        $acRes = _arrMinByField( $acRes, 'level' );
        $strJoin  = $acRes['group_id'].':'.$acRes['role_id'];

        switch ( $strJoin ) {
            case $role1 :
                $res = $this->_checkShopByAdduid('id,group_id,message,status', $uid, null, '2,3');
                if ($res === null) return [
                    'group_id'    => 0,
                    'role_id'     => _merchant_roleName(C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_COMMITINFO')),
                    'shop_id'     => 0,
                    'shop_status' => 0,
                    'shop_message'=> '',
                    'sys_message' => '注册后未填写店铺资料',
                ];
                return [
                    'group_id'    => $res['group_id'],
                    'role_id'     => _merchant_roleName(C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_COMMITINFO')),
                    'shop_id'     => $res['id'],
                    'shop_status' => $res['status'],
                    'shop_message'=> is_null($res['message']) ? '' : $res['message'],
                    'sys_message' => '注册后修改店铺资料',
                ];

                break;
            default     :
                $res = $this->_checkShopByAdduid('id,group_id,message,status', null, $acRes['group_id'], 1);
                if ($res === null) return [
                    'group_id'    => 0,
                    'role_id'     => _merchant_roleName($acRes['role_id']),
                    'shop_id'     => 0,
                    'shop_status' => 0,
                    'shop_message'=> '',
                    'sys_message' => '非正常操作的找不到店铺',
                ];
                return [
                    'group_id'    => $acRes['group_id'],
                    'role_id'     => _merchant_roleName($acRes['role_id']),
                    'shop_id'     => $res['id'],
                    'shop_status' => $res['status'],
                    'shop_message'=> '',
                    'sys_message' => '正常的店铺',
                ];
        }


    }

    protected function _checkShopByAdduid ($fields = '*', $uid = null, $group_id = null, $status = null) {
        $uid === null ? : $map['add_uid'] = $uid;
        $group_id === null ? : $map['group_id'] = $group_id;
        $map['status'] = count(explode(',', $status)) > 1 ? ['in',$status] : $status;
        $model = M('MerchantShop');
        return $model->field($fields)->where($map)->find();
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout($token){
        clear_merchant_login($token);
    }

    public function saveInfo( $data ) {
        try {

            empty($data) ? E('修改字段不能为空') : '';
            $data = $this->create($data);
            if($data['password']){
                $this->saltkey = SALTKEY;
                $this->password = $this->getPwd($data['password']);
            }

            if(empty($data))
                E('创建对象失败');
            if(empty($data['id'])){
                $id = $this->add();
                if(!$id)
                    E('新增失败');
            } else {
                $status = $this->save();
                if(false === $status)
                    E('更新失败');
                return true;
            }


        } catch (\Exception $ex) {

            return $ex->getMessage();

        }
    }


}
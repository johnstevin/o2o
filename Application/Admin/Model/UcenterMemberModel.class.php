<?php
// +----------------------------------------------------------------------
// | 公共调用类
// +----------------------------------------------------------------------
// | Author: stevin.john Date: 2015-5-21
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;
/**
 * 会员模型
 */
DEFINE('SALTKEY',generate_saltKey());

class UcenterMemberModel extends Model{

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

	/* 用户模型自动完成 */
	protected $_auto = array(
		array('password', 'getPwd', self::MODEL_INSERT, 'callback'),
        array('saltkey', SALTKEY),
		array('reg_time', NOW_TIME, self::MODEL_INSERT),
		array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
		array('update_time', NOW_TIME),
        array('is_member', 1),
        array('is_admin', 1),
	);

    final public function getPwd( $pwd ){
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
	public function register($mobile, $password, $username, $email){
		$data = array(
			'username' => $username,
			'password' => $password,
			'email'    => $email,
			'mobile'   => $mobile,
		);

		if(empty($data['username'])) unset($data['username']);
        if(empty($data['email']))    unset($data['email']);

		/* 添加用户 */
		if($this->create($data)){
			$uid = $this->add();
			return $uid ? $uid : 0; //0-未知错误，大于0-注册成功
		} else {
			return $this->getError();
		}
	}

	/**
	 * 用户登录认证
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
		if(is_array($user) && $user['is_admin']){
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
     * 更新或插入管理员登录信息
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
        $admin = M(self::USER_ADMIN);
        $result = $admin->field('id')->where('id='.$user['id'])->find();
        if( $result ){
            $admin->save($data);
        }else{
            $admin->add($data);
        }

        if ($admin->getDbError())
            return -3;  //插入或更新管理员信息失败

        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid'             => $user['id'],
            'mobile'          => $user['mobile'],
            'last_login_time' => $data['last_login_time'],
        );

        session('admin_auth', $auth);
        session('admin_auth_sign', data_auth_sign($auth));

        return $user['id'];
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        session('admin_auth', null);
        session('admin_auth_sign', null);
    }

	/**
	 * 获取用户信息
	 * @param  string  $uid         用户ID或用户名
	 * @param  boolean $is_username 是否使用用户名查询
	 * @return array                用户信息
	 */
	public function info($uid, $is_username = false){
		$map = array();
		if($is_username){ //通过用户名获取
			$map['username'] = $uid;
		} else {
			$map['id'] = $uid;
		}

		$user = $this->where($map)->field('id,username,email,mobile,status')->find();
		if(is_array($user) && $user['status'] == 1){
			return array($user['id'], $user['username'], $user['email'], $user['mobile']);
		} else {
			return -1; //用户不存在或被禁用
		}
	}

	/**
	 * 检测用户信息
	 * @param  string  $field  用户名
	 * @param  integer $type   用户名类型 1-用户名，2-用户邮箱，3-用户电话
	 * @return integer         错误编号
	 */
	public function checkField($field, $type = 1){
		$data = array();
		switch ($type) {
			case 1:
				$data['username'] = $field;
				break;
			case 2:
				$data['email'] = $field;
				break;
			case 3:
				$data['mobile'] = $field;
				break;
			default:
				return 0; //参数错误
		}

		return $this->create($data) ? 1 : $this->getError();
	}



	/**
	 * 更新用户信息
	 * @param int $uid 用户id
	 * @param string $password 密码，用来验证
	 * @param array $data 修改的字段数组
	 * @return true 修改成功，false 修改失败
	 * @author huajie <banhuajie@163.com>
	 */
	public function updateUserFields($uid, $password, $data){
		if(empty($uid) || empty($password) || empty($data)){
			$this->error = '参数错误！';
			return false;
		}

		//更新前检查用户密码
		if(!$this->verifyUser($uid, $password)){
			$this->error = '验证出错：密码不正确！';
			return false;
		}

		//更新用户信息
		$data = $this->create($data);
		if($data){

            if($data['password']){
                $this->saltkey = SALTKEY;
                $this->password = $this->getPwd($data['password']);
            }
			return $this->where(array('id'=>$uid))->save($data);
		}

		return false;
	}

	/**
	 * 验证用户密码
	 * @param int $uid 用户id
	 * @param string $password_in 密码
	 * @return true 验证成功，false 验证失败
	 * @author huajie <banhuajie@163.com>
	 */
	protected function verifyUser($uid, $password_in){


        $user = $this->getByid($uid);

        if(generate_password($password_in, $user['saltkey']) === $user['password']){
            return true;
        }

		return false;
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
     * 获取用户列表
     * @param 条件：admin 管理员，member 普通用户，merchant 商户
     * @param $method
     * @return array|string
     */
     public function userList($method){
        $UserInfo=array();
        switch (strtolower($method)) {
            case 'admin':

                $map=array('is_admin'=>array('eq', '1'));


                /*分页*/
                $total = $this->where($map)->count();

                if (isset($REQUEST['r'])) {
                    $listRows = (int)$REQUEST['r'];
                } else {
                    $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
                }

                $page = new \Think\Page($total, $listRows);

                $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');

                $options['limit'] = $page->firstRow . ',' . $page->listRows;

                $this->setProperty('options', $options);


               /*查询*/
                $UserInfo=$this
                    ->field('a.id,a.mobile,a.username,a.email,a.reg_time,b.status,b.last_login_ip,b.last_login_time')
                    ->table('__UCENTER_MEMBER__ a')
                    ->join('__ADMIN__ b ON  a.id = b.login','LEFT')
                    ->where($map)
                    ->select();


                /*返回结果*/
                return [
                    'data' => $UserInfo,
                    '_page' => $page->show()
                ];
                break;


            case'member':

                $map=array('is_member'=>array('eq', '1'));


                /*分页*/
                $total = $this->where($map)->count();

                if (isset($REQUEST['r'])) {
                    $listRows = (int)$REQUEST['r'];
                } else {
                    $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
                }

                $page = new \Think\Page($total, $listRows);

                $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');

                $options['limit'] = $page->firstRow . ',' . $page->listRows;

                $this->setProperty('options', $options);


                $UserInfo=$this
                    ->field('a.id,a.mobile,a.username,a.email,a.reg_time,b.status,b.last_login_ip,b.last_login_time')
                    ->table('__UCENTER_MEMBER__ a')
                    ->join('__MEMBER__ b ON  a.id = b.login','LEFT')
                    //->where(array('a.is_admin'=>array('neq', '1'),'a.is_merchant'=>array('neq', '1'),'a.is_member'=>array('eq', '1')))
                    ->where($map)
                    ->select();


                /*返回结果*/
                return [
                    'data' => $UserInfo,
                    '_page' => $page->show()
                ];
                break;


            case'merchant':

                $map=array('is_merchant'=>array('eq', '1'));

                /*分页*/
                $total = $this->where($map)->count();

                if (isset($REQUEST['r'])) {
                    $listRows = (int)$REQUEST['r'];
                } else {
                    $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
                }

                $page = new \Think\Page($total, $listRows);

                $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');

                $options['limit'] = $page->firstRow . ',' . $page->listRows;

                $this->setProperty('options', $options);

                $UserInfo=$this
                    ->field('a.id,a.mobile,a.username,a.email,a.reg_time,b.status,b.last_login_ip,b.last_login_time')
                    ->table('__UCENTER_MEMBER__ a')
                    ->join('__MERCHANT__ b ON  a.id = b.login','LEFT')
                    //->where(array('a.is_admin'=>array('neq', '1'),'a.is_merchant'=>array('eq', '1')))
                    ->where($map)
                    ->select();


                /*返回结果*/
                return [
                    'data' => $UserInfo,
                    '_page' => $page->show()
                ];
                break;


            default:
                $this->error('参数错误');
                break;

        }
        return $UserInfo;

    }

}

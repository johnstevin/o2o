<?php

namespace Common\Model;

use Think\Model\RelationModel;

/**
 * 用户模型
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class MemberModel extends RelationModel
{
    protected static $model;
    ## 状态常量
    const STATUS_DELETE = -1;
    const STATUS_LOCK = 0;
    const STATUS_ACTIVE = 1;

    protected $_validate = [
        ['nickname', '1,16', '昵称长度为1-16个字符', self::EXISTS_VALIDATE, 'length'],
        ['nickname', '', '昵称被占用', self::EXISTS_VALIDATE, 'unique'], //用户名被占用
    ];

    public function lists($status = 1, $order = 'uid DESC', $field = true)
    {
        $map = ['status' => $status];
        return $this->field($field)->where($map)->order($order)->select();
    }

    /**
     * 获得当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return MemberModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 检测用户是否合法
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param $id
     * @return bool
     */
    public static function checkUserExist($id)
    {
        $id = intval($id);
        return ($id && self::getById($id, self::STATUS_ACTIVE, 'uid')) ? true : false;
    }

    /**
     * 根据ID查找用户
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 用户ID
     * @param null|int $status 用户状态
     * @param string|array $fileds 要查询的字段
     * @return null|array
     */
    public static function getById($id, $status = null, $fileds = '*')
    {
        $where['uid'] = $id;
        $where['status'] = ($status && in_array($status, array_keys(self::getStatusOptions()))) ? $status : self::STATUS_ACTIVE;
        return self::getInstance()->where($where)->field($fileds)->find() ?: null;
    }

    /**
     * 获得所有用户状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '删除',
            self::STATUS_ACTIVE => '正常',
            self::STATUS_LOCK => '锁定'
        ];
    }

    /**
     * 登录指定用户
     * @param  integer $uid 用户ID
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid)
    {
        /* 检测是否在当前应用注册 */
        $user = $this->field(true)->find($uid);
        if (!$user || 1 != $user['status']) {
            $this->error = '用户不存在或已被禁用！'; //应用级别禁用
            return false;
        }

        //记录行为
        action_log('user_login', 'member', $uid, $uid);

        /* 登录用户 */
        $this->autoLogin($user);
        return true;
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout()
    {
        session('user_auth', null);
        session('user_auth_sign', null);
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($user)
    {
        /* 更新登录信息 */
        $data = [
            'uid' => $user['uid'],
            'login' => ['exp', '`login`+1'],
            'last_login_time' => NOW_TIME,
            'last_login_ip' => get_client_ip(1),
        ];
        $this->save($data);

        /* 记录登录SESSION和COOKIES */
        $auth = [
            'uid' => $user['uid'],
            'username' => $user['nickname'],
            'last_login_time' => $user['last_login_time'],
        ];

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));

    }

    public function getNickName($uid)
    {
        return $this->where(['uid' => (int)$uid])->getField('nickname');
    }

}

<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Think;

class Auth{

    //默认配置
    protected $_config = array(
        'AUTH_ON'           => true,                      // 认证开关
        'AUTH_TYPE'         => 1,                         // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP'        => 'auth_group',              // 组织数据表
        'AUTH_ROLE'         => 'auth_role',               // 角色－组织数据表
        'AUTH_RULE'         => 'auth_rule',               // 权限规则表
        'AUTH_ROLE_RULE'    => 'auth_role_rule',          // 角色－规则数据表
        'AUTH_ACCESS'       => 'auth_access',             // 用户-组织-角色关系表
        'AUTH_USER'         => 'ucenter_member'           // 用户表
    );

    public function __construct() {
        $prefix                             = C('DB_PREFIX');
        $this->_config['AUTH_GROUP']        = $prefix.$this->_config['AUTH_GROUP'];
        $this->_config['AUTH_ROLE']         = $prefix.$this->_config['AUTH_ROLE'];
        $this->_config['AUTH_RULE']         = $prefix.$this->_config['AUTH_RULE'];
        $this->_config['AUTH_ROLE_RULE']    = $prefix.$this->_config['AUTH_ROLE_RULE'];
        $this->_config['AUTH_ACCESS']       = $prefix.$this->_config['AUTH_ACCESS'];
        $this->_config['AUTH_USER']         = $prefix.$this->_config['AUTH_USER'];
        if (C('AUTH_CONFIG')) {
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }

    /**
      * 检查权限
      * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
      * @param uid  int           认证用户的id
      * @param string mode        执行check的模式
      * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
      * @return boolean           通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type=1, $mode='url', $relation='or') {
        if (!$this->_config['AUTH_ON'])
            return true;
        $authList = $this->getAuthList($uid,$type);
        //print_r($authList);exit;
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array();
        if ($mode=='url') {
            $REQUEST = unserialize( strtolower(serialize($_REQUEST)) );
        }

        foreach ( $authList as $auth ) {
            $query = preg_replace('/^.+\?/U','',$auth);
            if ($mode=='url' && $query!=$auth ) {
                parse_str($query,$param);
                $intersect = array_intersect_assoc($REQUEST,$param);
                $auth = preg_replace('/\?.*$/U','',$auth);
                if ( in_array($auth,$name) && $intersect==$param ) {  //如果节点相符且url参数满足
                    $list[] = $auth ;
                }
            }else if (in_array($auth , $name)){
                $list[] = $auth ;
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 获得权限列表
     * @param integer $uid  用户id
     * @param integer $type
     */
    protected function getAuthList($uid,$type) {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',',(array)$type);
        if (isset($_authList[$uid.$t])) {
            return $_authList[$uid.$t];
        }
        if( $this->_config['AUTH_TYPE']==2 && isset($_SESSION['_AUTH_LIST_'.$uid.$t])){
            return $_SESSION['_AUTH_LIST_'.$uid.$t];
        }
        $groups = $this->getGroups($uid);
        $roles = array();
        foreach($groups as $gro){
            foreach($gro as $r){
                $roles[] = $r;
            }
        }
        $roles = array_unique($roles);
        // 获取规则
        $ids = $this->getRules($roles);
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid.$t] = array();
            return array();
        }

        $map=array(
            'id'=>array('in',$ids),
            'type'=>$type,
            'status'=>1,
        );
        //读取用户组所有权限规则
        $rules = M()
            ->table($this->_config['AUTH_RULE'])
            ->where($map)
            ->field('title,module,url')->select();

        $authList = array();
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) {
                // 这里可以做业务权限
                $user = $this->getUserInfo($uid);
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', '{score}>5  and {score}<100')
                //echo 结果："$user['score']>5  and $user['score']<100"
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['url']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['url']);
            }
        }
        $_authList[$uid.$t] = $authList;
        if($this->_config['AUTH_TYPE']==2){
            //规则列表结果保存到session
            $_SESSION['_AUTH_LIST_'.$uid.$t]=$authList;
        }
        return array_unique($authList);
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  uid int     用户id
     * @return array       用户所属的用户组
     */
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = M()
            ->table($this->_config['AUTH_ACCESS'] . ' a')
            ->where("a.uid='$uid' and a.status=1 and g.status=1")
            ->join($this->_config['AUTH_GROUP']." g on a.group_id=g.id")
            ->field('a.uid,a.group_id,a.role_id,g.title')->select();
        foreach($user_groups as $val){
            $groups[$uid][$val['group_id']][] = $val['role_id'];
        }
        return $groups[$uid];
    }

    /**
     * @param array $roles
     * @return array
     */
    public function getRules($roles = array()){
        static $rules = array();
        $roles = implode(',',$roles);
        $map['role_id'] = array('in',$roles);
        $user_rules = M()
            ->table($this->_config['AUTH_ROLE_RULE'])
            ->where($map)
            ->field('rule_id')->select();
        foreach($user_rules as $rule){
            $rules[] = $rule['rule_id'];
        }
        return $rules;
    }

    /**
     * @param $uid
     * @return mixed
     */
    protected function getUserInfo($uid) {
        static $userinfo=array();
        if(!isset($userinfo[$uid])){
            $userinfo[$uid]=M()->where(array('id'=>$uid))->table($this->_config['AUTH_USER'])->find();
        }
        return $userinfo[$uid];
    }

}

<?php

namespace Admin\Model;

use Think\Model;

/**
 * 用户角色模型类
 * Class AuthRoleModel
 * @package Common\Model
 * @author liuhui
 */
class AuthRoleModel extends Model
{

    const TYPE_ADMIN = 1; // 管理员用户组类型标识
    const AUTH_ACCESS = 'auth_access'; // 关系表表名

    protected $_validate = array(
        array('group_id', '0', '必须选择组织', self::MUST_VALIDATE, 'notequal', self::MODEL_BOTH),
        array('description', '0,80', '描述最多80字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
    );

//    protected $_link = array(
//        'Roles' => array(
//            'mapping_type' => self::HAS_MANY,
//            'class_name' => 'auth_access',
//            'foreign_key' => 'group_id',
//            'mapping_name' => '_roles',
//            'mapping_order' => 'role_id desc',
//    )
//    );

    /**
     * 返回角色列表
     * 默认返回正常状态的管理员用户组列表
     * @param array $where 查询条件,供where()方法使用
     *
     * @return array 返回用户组列表
     */
    public function getRoles($where = array())
    {
        $map = array('status' => 1, 'type' => self::TYPE_ADMIN, 'module' => 'admin');
        $map = array_merge($map, $where);
        return $this->where($map)->select();
    }

    /**
     * 返回用户所属角色信息
     * @param  int $uid 用户id
     * @return array  用户所属的角色 array(uid'=>'用户id','roles'=>'角色id','rules'=>'用户组拥有的规则id,多个,号隔开')
     */
    static public function getUserRole($uid)
    {
        static $roles = array();
        if (isset($roles[$uid]))
            return $roles[$uid];
        $prefix = C('DB_PREFIX');
        $user_roles = M()
            ->field('uid,roles,rules')
            ->table($prefix . self::AUTH_ACCESS . ' a')
            ->where("a.uid='$uid'")
            ->find();
        $roles[$uid] = $user_roles ? $user_roles : array();
        return $roles[$uid];
    }


    /**
     * 把用户添加到用户组,支持批量添加用户到用户组
     *
     * 示例: 把uid=1的用户添加到group_id为1,2的组 `AuthGroupModel->addToGroup(1,'1,2');`
     * $gid array(array())二位数组
     */
    public function addToRole($uid, $gid)
    {

        $uid = is_array($uid) ? implode(',', $uid) : trim($uid, ',');
        $gid = is_array($gid) ? $gid : explode(',', trim($gid, ','));

        $Access = M(self::AUTH_ACCESS);
        if (isset($_REQUEST['batch'])) {
            //为单个用户批量添加用户组时,先删除旧数据
            $del = $Access->where(array('uid' => array('in', $uid)))->delete();
        }

        $uid_arr = explode(',', $uid);
        $uid_arr = array_diff($uid_arr, array(C('USER_ADMINISTRATOR')));
        $add = array();
        if ($del !== false) {
            foreach ($uid_arr as $u) {
                //判断用户id是否合法
                if (M('Member')->getFieldByUid($u, 'uid') == false) {
                    $this->error = "编号为{$u}的用户不存在！";
                    return false;
                }
                foreach ($gid as $k => $val) {
                    foreach ($val as $g) {
                        if (is_numeric($u) && is_numeric($g)) {
                            $add[] = array('group_id' => $k, 'uid' => $u, 'role_id' => $g,'status'=>'1');
                        }
                    }
                }
            }
            $Access->addAll($add);
        }
        if ($Access->getDbError()) {
            if (count($uid_arr) == 1 && count($gid) == 1) {
                //单个添加时定制错误提示
                $this->error = "不能重复添加";
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * 更新角色信息
     * @return boolean 更新状态
     */
    public function update()
    {
        $data = $this->create();
        if (!$data) { //数据对象创建错误
            return false;
        }

        /* 添加或更新数据 */
        if (empty($data['id'])) {
            $res = $this->add();
        } else {
            $res = $this->save();
        }

        return $res;
    }

    /**
     * @param  检查id是否全部存在
     * @param $mid
     * @param string $msg
     * @return array|string $gid  用户组id列表
     */
    public function checkId($modelname, $mid, $msg = '以下id不存在:')
    {
        if (is_array($mid)) {
            $count = count($mid);
            $ids = implode(',', $mid);
        } else {
            $mid = explode(',', $mid);
            $count = count($mid);
            $ids = $mid;
        }

        $s = M($modelname)->where(array('id' => array('IN', $ids)))->getField('id', true);
        if (count($s) === $count) {
            return true;
        } else {
            $diff = implode(',', array_diff($mid, $s));
            $this->error = $msg . $diff;
            return false;
        }
    }

    /**
     * 检查角色组是否全部存在
     * @param $gid
     * @return array|string
     */
    public function checkRoleId($gid)
    {
        return $this->checkId('AuthRole', $gid, '以下角色组id不存在:');
    }

    /**
     * 获取用户组详细信息
     * @param  milit $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function info($id, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        if (is_numeric($id)) { //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

}
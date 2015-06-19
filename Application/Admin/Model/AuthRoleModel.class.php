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
    const ROLE_ADMIN = 1;

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

            /* 判断权限*/
            if (!IS_ROOT) {
                $group_id = I('group_id');
                if (!(in_array($group_id, D('AuthGroup')->UserAuthGroup()))) {
                    $this->error = '权限不足，请联系管理员';
                    return false;
                }
            }

            $res = $this->add();
        } else {
            if (!IS_ROOT) {
                if (!(in_array($data['id'], $this->UserAuthRole()))) {
                    $this->error = '权限不足，请联系管理员';
                    return false;
                }
            }
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
     * @author liuhui
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

    /**
     * 返回拥有权限的角色
     * @return array 权限数组
     * @author liuhui
     */
    public function UserAuthRole()
    {

        $userRole = S(UID . 'AUTH_ROLE');
        if (empty($userRole)) {
//            $userRole = D('AuthAccess')->getUserRole(UID);

            $AuthGoup = D('AuthGroup');
            $UserAuthGroup = $AuthGoup->UserAuthGroup();
            $map['group_id']=   array('in',$UserAuthGroup);
            $map['status']  =   array('egt',0);
            $userRole = $this->field('id')->where($map)->select();
            $userRole = array_column($userRole, 'id');
            S(UID . 'AUTH_ROLE', $userRole);
        }
        return $userRole;
    }

}
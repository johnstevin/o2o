<?php
// +----------------------------------------------------------------------
// | 公共调用类
// +----------------------------------------------------------------------
// | Author: stevin.john Date: 2015-5-21
// +----------------------------------------------------------------------

namespace Admin\Model;

use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 * @author stevin.john
 */
class AuthAccessModel extends Model
{
    const TYPE_ADMIN = 1;                   // 管理员用户组类型标识
    const TYPE_MERCHANT = 2;                   // 商户用户组类型标识
    const TYPE_MEMBER = 3;                   // 用户组类型标识
    const UCENTER_MEMBER = 'ucenter_member';
    const AUTH_ACCESS = 'auth_access'; // 关系表表名
    const AUTH_EXTEND = 'auth_extend';       // 动态权限扩展信息表
    const AUTH_GROUP = 'auth_group';        // 组织表名

    protected $_validate = array();

    /**
     * 给用户分配权限
     * @param $data
     * @return bool|int
     * @author stevin.john
     */
    public function addUserAccess($data)
    {
        $this->addAll($data);
        if ($this->getDbError()) {
            return -13;
        } else {
            return true;
        }
    }

    /**
     * 返回用户组列表
     * 默认返回正常状态的管理员用户组列表
     * @param array $where 查询条件,供where()方法使用
     * @author stevin.john
     */
    public function getGroups($map = array())
    {
        return $this->where($map)->select();
    }

    /**
     * 把用户添加到用户组,支持批量添加用户到用户组
     *
     * 示例: 把uid=1的用户添加到group_id为1,2的组 `AuthGroupModel->addToGroup(1,'1,2');`
     * $gid array(array())二位数组
     * @author liuhui
     */
    public function addToRole($uid, $gid,$type)
    {

        $uid = is_array($uid) ? implode(',', $uid) : trim($uid, ',');
        $gid = is_array($gid) ? $gid : explode(',', trim($gid, ','));


        //TODO 事物控制
        $Access = M(self::AUTH_ACCESS);
        if (isset($_REQUEST['batch'])) {

           $map= array('uid' => array('in', $uid));

            //先删除旧数据
            switch (strtolower($type)) {
                case '1':
                    $where = array('type' => C('AUTH_GROUP_TYPE')['ADMIN']);
                    break;
                case'3':
                    $where = array('type' => C('AUTH_GROUP_TYPE')['MEMBER']);
                    break;
                case'2':
                    $where = array('type' => C('AUTH_GROUP_TYPE')['MERCHANT']);
                    break;
                default:
                    $this->error='参数错误';
                    return false;
                    break;
            }

            $ids = M('AuthGroup')->field('id')->where($where)->select();

            $ids=array_column($ids,'id');

            $w = array('group_id'=>array('in',$ids));

            $map=array_merge($map,$w);

            $del = $Access->where($map)->delete();

        }

        $uid_arr = explode(',', $uid);
        $uid_arr = array_diff($uid_arr, array(C('USER_ADMINISTRATOR')));
        $add = array();

        if ($del !== false) {


            /*权限控制*/
//            $AuthGroup = D('AuthGroup')->UserAuthGroup();
//            $AuthRole = D('AuthRole')->UserAuthRole();
            foreach ($uid_arr as $u) {
//                判断用户id是否合法
//                if (!IS_ROOT) {
//                    if (M('MerchantShop') == false) {
//                        $this->error = "编号为{$u}的用户不合法！";
//                        return false;
//                    }
//                }
                foreach ($gid as $k => $val) {
                    foreach ($val as $g) {

                        if (is_numeric($u) && is_numeric($g)) {
//                            if (!IS_ROOT) {
//                                if (!in_array($k, $AuthGroup) || !in_array($g, $AuthRole)) {
//                                    $this->error = "编号为{$k}的组织会编号为{$g}的角色不存在！";
//                                    return false;
//                                }
//                            }
                            $add[] = array('group_id' => $k, 'uid' => $u, 'role_id' => $g, 'status' => '1');


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
     * 审核授权
     * 示例: 把uid=1的用户添加到group_id为1,2的组 `AuthGroupModel->addToGroup(1,'1,2');`
     * $gid array(array())二位数组
     * @author liuhui
     */
    public function CheckMerchantRole($uid, $gid,$rid)
    {

        if (!is_numeric($uid) && !is_numeric($gid)&&!is_numeric($rid)) {
            $this->error = "参数非法";
            return false;
        }
        /*删除以前的数据*/
        $map= array(
            'uid' => $uid,
            'group_id' => C('AUTH_GROUP_ID')['GROUP_ID_MERCHANT'],
            'role_id'=>C('AUTH_ROLE_ID')['ROLE_ID_MERCHANT_COMMITINFO'],
        );

        $del = $this->where($map)->delete();

        $add = array();

        if ($del !== false) {

              $add['uid'] = $uid;
              $add['group_id'] = $gid;
              $add['role_id'] = $rid;
              $add['status'] = 1;
              $add['description']='审核插入的数据';

            }
            $this->add($add);

        if ($this->getDbError()) {

            return false;
        } else {

            return true;
        }
    }





    /**
     * 返回用户所属角色信息
     * @param  int $uid 用户id
     * @return array  用户所属的角色 array(uid'=>'用户id','roles'=>'角色id','rules'=>'用户组拥有的规则id,多个,号隔开')
     * @author liuhui
     */
    static public function getUserRole($uid)
    {
        static $roles = array();
        if (isset($roles[$uid]))
            return $roles[$uid];
        $prefix = C('DB_PREFIX');
        $user_roles = M()
            ->field('a.role_id')
            ->table($prefix . self::AUTH_ACCESS . ' a')
            ->where("a.uid='$uid'")
            ->distinct(true)
            ->select();
        $roles[$uid] = $user_roles ? $user_roles : array();
        return $roles[$uid];
    }

    /**
     * 返回用户所拥有的组织
     * @param  int $uid 用户id
     * @param int $type 用户组类型1-管理员，2-商户，3-用户
     * @return array  用户所拥有的组织
     * @author liuhui
     */
    static public function getUserGroup($uid, $type = null)
    {
        static $group = array();
        if (isset($group[$uid]))
            return $group[$uid];
        $prefix = C('DB_PREFIX');
        if ($type == 1) {
            $user_groups = M()
                ->field('a.group_id')
                ->table($prefix . self::AUTH_ACCESS . ' a')
                ->join('__AUTH_GROUP__ b ON a.group_id= b.id')
                ->where(array('a.uid' => $uid, 'b.type' => C('auth_group_type')['ADMIN']))
                ->distinct(true)
                ->select();
        } else if ($type == 2) {
            $user_groups = M()
                ->field('a.group_id')
                ->table($prefix . self::AUTH_ACCESS . ' a')
                ->join('__AUTH_GROUP__ b ON a.group_id= b.id')
                ->where(array('a.uid' => $uid, 'b.type' => C('auth_group_type')['MERCHANT']))
                ->distinct(true)
                ->select();
        } else if ($type == 3) {
            $user_groups = M()
                ->field('a.group_id')
                ->table($prefix . self::AUTH_ACCESS . ' a')
                ->join('__AUTH_GROUP__ b ON a.group_id= b.id')
                ->where(array('a.uid' => $uid, 'b.type' => C('auth_group_type')['MEMBER']))
                ->distinct(true)
                ->select();
        } else {
            $user_groups = M()
                ->field('a.group_id')
                ->table($prefix . self::AUTH_ACCESS . ' a')
                ->where(array('a.uid' => $uid))
                ->distinct(true)
                ->select();
        }
        $group[$uid] = $user_groups ? $user_groups : array();
        return $group[$uid];
    }


    /**
     * 管理员改变成商家
     * @param $uid
     * @return bool
     */
    public function changeGroup(){

        $uid=I('uid');
        if(empty($uid)){
            $this->error='参数非法';
            return false;
        }

        $group_id= C('AUTH_GROUP_ID')['GROUP_ID_MERCHANT'];
        $role_id= C('AUTH_ROLE_ID')['ROLE_ID_MERCHANT_COMMITINFO'];
        $map=array(
            'uid'=>$uid,
            'group_id'=>$group_id,
            'role_id'=>$role_id,
        );
        $data=array(
            'uid'=>$uid,
            'group_id'=>$group_id,
            'role_id'=>$role_id,
            'description'=>"管理员=>商家",
            'status'=>1,
        );


        $where=$this->where($map)->count();


        M()->startTrans();

        if(false!==D('UcenterMember')->where(array('id'=>$uid))->setField('is_merchant',1)) {
            if ($where<=0) {

                if(false!==$this->add($data)){

                    M()->commit();
                    return true;

                }else{

                    M()->rollback();

                    $this->error='保存失败';
                    return false;

                }
            }else{

                M()->commit();
                return true;
            }
        }else{

            M()->rollback();

            $this->error='保存失败';
            return false;

        }

    }

}


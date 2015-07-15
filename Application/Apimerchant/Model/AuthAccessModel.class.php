<?php
// +----------------------------------------------------------------------
// | 公共调用类
// +----------------------------------------------------------------------
// | Author: stevin.john Date: 2015-5-21
// +----------------------------------------------------------------------

namespace Apimerchant\Model;
use Think\Model\AdvModel;

/**
 * 权限模型类
 * Class AuthAccessModel
 * @author stevin.john
 */
class AuthAccessModel extends AdvModel {
    const TYPE_ADMIN                = 1;                   // 管理员用户组类型标识
    const TYPE_MERCHANT             = 2;                   // 商户用户组类型标识
    const TYPE_MEMBER               = 3;                   // 用户组类型标识
    const UCENTER_MEMBER            = 'ucenter_member';
    const AUTH_GROUP_ACCESS         = 'auth_access';       // 关系表表名
    const AUTH_EXTEND               = 'auth_extend';       // 动态权限扩展信息表
    const AUTH_GROUP                = 'auth_group';        // 组织表名

    const AUTH_STATUS_DELETE         = -1;
    const AUTH_STATUS_AWAIT         = 0;                   // 待审核
    const AUTH_STATUS_PASS          = 1;                   // 审核通过
    const AUTH_STATUS_NOPASS        = 2;                   // 审核未通过

    protected $_validate = array(

    );

    /**
     * 给用户分配权限
     * @param $data
     * @return bool|int
     * @author stevin.john
     */
    public function addUserAccess($data){
        $this->addAll($data);
        if ($this->getDbError()) {
            return -13;
        }else{
            return true;
        }
    }

    /**
     * 根据条件查询
     * @param $map
     * @param $field
     * @return array|int
     */
    public function get( $map, $field='*' ){
        return $this->field($field)->where($map)->select();
    }

    /**
     * TODO 这里可以做一些高级筛选
     * @param $map
     * @param string $field
     * @return bool|mixed
     */
    public function lists( $map, $field='*' ) {
        $result = $this->field($field)->where($map)->select();
        if( empty($result) ){
            return false;
        } else {
            return $result;
        }
    }

    /**
     * 员工审核->成功
     * @param $uid
     * @param $group_id
     * @param $role_id
     * @return bool
     */
    public function CheckSuccess($uid,$group_id,$role_id){
        $map = array(
            'uid'       =>$uid,
            'group_id'  => $group_id,
            'role_id'   => $role_id,
            'status'    =>array('neq','-1'),
        );
        return  $this->where($map)->setField('status',self::AUTH_STATUS_PASS);
    }


    /**
     * 员工审核->失败
     * @param $uid
     * @param $group_id
     * @param $role_id
     * @return bool
     */
    public function CheckFail($uid,$group_id,$role_id){
        $map = array(
            'uid'       =>$uid,
            'group_id'  => $group_id,
            'role_id'   => $role_id,
            'status'    =>array('neq','-1'),
        );

        M()->startTrans();

       if(false!==$this->where($map)->delete()){
           if(false!==M('UcenterMember')->where(array('id'=>$uid))->delete()){
               if(false!==M('Merchant')->where(array('id'=>$uid))->delete()){
                   M()->commit();
                   return true;
           }else{
               M()->rollback();
               E('保存失败');
           }
           }else{
               M()->rollback();
               E('保存失败');
           }
       } else{
          M()->rollback();
           E('保存失败');
       };
    }


    public function staffDelete($uid,$group_id,$role_id){
        $map = array(
            'uid'       =>$uid,
            'group_id'  => $group_id,
            'role_id'   => $role_id,
            'status'    =>array('neq','-1'),
        );
        return $this->where($map)->setField('status',self::AUTH_STATUS_DELETE);

    }

}


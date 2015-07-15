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
        $result = $this->field($field)->where($map)->select();
        if( empty($result) ){
            return -1; //
        } else {
            foreach ($result as $v) {
                $tempArr[] = $v['uid'];
            }
            return array_unique($tempArr);
        }



    }

    /**
     * @param string $field
     * @param int $type
     * @param int $uid
     * @param int $group_id
     * @param int $role_id
     * @param int $status
     * @return bool
     * @author Stevin.John@qq.com
     */
    public function lists( $field = '*', $type = 0, $uid = 0, $group_id = 0, $role_id = 0, $status = null ) {
        $uid == 0 ? '' :        $map['a.uid'] = $uid;
        $group_id == 0 ? '' :   $map['a.group_id'] = $group_id;
        $role_id == 0 ? '' :    $map['a.role_id'] = $role_id;
        $status === null ? '' : $map['a.status'] = $status;
        $type == 0 ? '' :       $map['g.type'] = $type;
        $acLists = $this
                    ->field($field)
                    ->table('__AUTH_ACCESS__ a')
                    ->join('__AUTH_GROUP__ g ON g.id = a.group_id')
                    ->where($map)
                    ->select();
        return $acLists;
    }


}


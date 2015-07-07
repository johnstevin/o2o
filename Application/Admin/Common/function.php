<?php
/**
 * 获取用户组信息，适用模板调用
 * @param $id
 * @param $field
 * @return mixed
 */
function getGroupById($id, $field){
    $id = (int)($id);
    $result = D("AuthGroup")->info($id, $field);
    return empty($result) ? '未知组织' : $result[$field];
}

/**
 * 获取店铺类型，适用模板调用
 * @param $type
 * @return string
 */
function getShopType($type){
    $type = (int)($type);
    return array_key_exists($type, C('SHOP_TYPE')) === true ? C('SHOP_TYPE')[$type] : '未知类型';
}

/**
 * 转换状态，适用于模板调用
 * @param $status
 * @return string
 */
function getShopStatus($status){
    $status = (int)($status);
    switch($status){
        case -1 : return '已删除';     break;
        case 0  : return '待审核';     break;
        case 1  : return '审核通过';   break;
        case 2  : return '审核中';     break;
        case 3  : return '审核未通过';  break;
        default : return '未知状态';   break;
    }
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type=0){
    $list = C('CONFIG_TYPE_LIST');
    return $list[$type];
}

/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group=0){
    $list = C('CONFIG_GROUP_LIST');
    return $group?$list[$group]:'';
}


// 获取数据的状态操作
function show_status_op($status) {
    switch ($status){
        case 0  : return    '启用';     break;
        case 1  : return    '禁用';     break;
        case 2  : return    '审核';       break;
        default : return    false;      break;
    }
}
/**
 * 获取行为数据
 * @param string $id 行为id
 * @param string $field 需要获取的字段
 * @return bool
 */
function get_action($id = null, $field = null){
    if(empty($id) && !is_numeric($id)){
        return false;
    }
    $list = S('action_list');
    if(empty($list[$id])){
        $map = array('status'=>array('gt', -1), 'id'=>$id);
        $list[$id] = M('Action')->where($map)->field(true)->find();
    }
    return empty($field) ? $list[$id] : $list[$id][$field];
}

/**
 * 根据条件字段获取数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @return bool|mixed|\Think\Model
 */
function get_document_field($value = null, $condition = 'id', $field = null){
    if(empty($value)){
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M('Model')->where($map);
    if(empty($field)){
        $info = $info->field(true)->find();
    }else{
        $info = $info->getField($field);
    }
    return $info;
}
/**
 * 获取行为类型
 * @param int $type 类型
 * @param bool $all 是否返回全部类型
 * @return array
 * @author liu hui
 */
function get_action_type($type, $all = false){
    $list = array(
        1=>'管理员',
        2=>'商家',
        3=>'用户',
    );
    if($all){
        return $list;
    }
    return $list[$type];
}

/**
 * @param $type
 * @return mixed
 */
function int_to_package_type($type){
    $list = array(
        1=>'用户',
        2=>'商家',
    );
    return $list[$type];
}

/**
 * @param $type
 * @return mixed
 */
function int_to_version_type($type){
    $list = array(
          1=>'基础版',
          2=>'内测版',
          3=>'公测版',
          4=>'候选版',
          5=>'发行版',
    );
    return $list[$type];
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 */
function check_verify($code, $id = 1){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}
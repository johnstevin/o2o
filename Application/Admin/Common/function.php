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
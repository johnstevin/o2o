<?php
return [
    /* 模块相关配置 */
    'AUTOLOAD_NAMESPACE' => ['Addons' => './Addons/'], //扩展模块列表
    'DEFAULT_MODULE' => 'Home',
    'MODULE_DENY_LIST' => ['Common', 'User', 'Admin'],
    //'MODULE_ALLOW_LIST'  => array('Home','Admin'),

    /* 用户相关设置 */
    'USER_MAX_CACHE' => 1000, //最大缓存用户数
    'USER_ADMINISTRATOR' => 1, //管理员用户ID

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL' => 0, //URL模式
    'VAR_URL_PARAMS' => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR' => '/', //PATHINFO URL分割符

    /* 全局过滤配置 */
    'DEFAULT_FILTER' => '', //全局过滤函数

    /* 文档模型配置 (文档模型核心配置，请勿更改) */
    'DATE_FORMAT' => 'Y-m-d H:i:s',

    /* 权限组配置-数据库id值 */
    'AUTH_GROUP_ID' => array(
        'GROUP_ID_MERCHANT'                 => 2,   //总商户组
        'GROUP_ID_MEMBER'                   => 3,   //总用户组
        'GROUP_ID_ADMIN'                    => 4,   //总管理员组

        'GROUP_ID_MERCHANT_SHOP'            => 17,  //总商户组－商超组
        'GROUP_ID_MERCHANT_VEHICLE'         => 18,  //总商户组－洗车组

        'GROUP_ID_MEMBER_CLIENT'            => 29,  //总用户组下级顾客组
    ),
    /* 权限角色配置-数据库id值 */
    'AUTH_ROLE_ID' => array(
        'ROLE_ID_MERCHANT_COMMITINFO'       => 5,   //总商户组－提交资料角色
        'ROLE_ID_MERCHANT_SHOP_BOSS'        => 4,   //总商户组－商超组－老板
        'ROLE_ID_MERCHANT_SHOP_MANAGER'     => 0,   //总商户组－商超组－店长
        'ROLE_ID_MERCHANT_SHOP_STAFF'       => 0,   //总商户组－商超组－员工
        'ROLE_ID_MERCHANT_VEHICLE_MANAGER'  => 0,   //总商户组－洗车组－管理
        'ROLE_ID_MERCHANT_VEHICLE_WORKER'   => 0,   //总商户组－洗车组－工人

        'ROLE_ID_MEMBER_CLIENT'             => 1,   //顾客组下的普通用户角色
    ),

    /* 用户组配置 */
    'AUTH_GROUP_TYPE'  => array(
        'ADMIN'    => 1,
        'MERCHANT' => 2,
        'MEMBER'   => 3,
    ),

    /* 店铺TAG配置 */
    'SHOP_TAG'         => array(
        1   => '超市',
        2   => '生鲜',
        3   => '送水',
    ),

];

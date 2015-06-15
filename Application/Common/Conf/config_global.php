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
    'DOCUMENT_MODEL_TYPE' => [2 => '主题', 1 => '目录', 3 => '段落'],
    'DATE_FORMAT' => 'Y-m-d H:i:s',

    /* 权限组配置 */
    'AUTH_GROUP_ID' => array(
        'MERCHANT_GROUP_ID' => 2,   //总商户组
        'MEMBER_GROUP_ID'   => 3,   //总用户组
        'ADMIN_GROUP_ID'    => 4,   //总管理员组
        'CLIENT_GROUP_ID'   => 29,  //总用户组下级顾客组
    ),
    /* 权限角色配置 */
    'AUTH_ROLE_ID' => array(
        'CLIENT_ROLE_ID'         => 1,   //顾客组下的普通用户角色
        'MERCHANT_COMMIT_INFO'   => 5,   //总商户组下的提交资料角色
    ),

    /* 店铺配置 */
    'SHOP_TYPE'    => array(17 => '超市', 89 => '生鲜', 18 => '洗车', 90 => '送水'),  //键值为组id

    /* 用户组配置 */
    'AUTH_GROUP_TYPE'  => array(
        'ADMIN'    => 1,
        'MERCHANT' => 2,
        'MEMBER'   => 3,
    ),

];

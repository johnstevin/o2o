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

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => '$5sdhnr4%#^d9smjf&345sfsdb2f4%*)&*)@#@!$sanhgh645#@#sdf', //默认数据加密KEY

    ## 极光推送设置
    'PUSH_TYPE' => 'JPush',
    'JPUSH_STORE_APP_KEY' => '8548f5acaccf5ece222be8c0',
    'JPUSH_STORE_MASTER_SECRET' => 'cc6141c26da194563766896c',
    'JPUSH_CLIENT_APP_KEY' => '9c987ff4739ce826e20e7920',
    'JPUSH_CLIENT_MASTER_SECRET' => '018dd4b4c6b576ab7d98118c',

    /* 权限组配置-数据库id值 */
    'AUTH_GROUP_ID' => [
        'GROUP_ID_MERCHANT' => 2,   //总商户组
        'GROUP_ID_MEMBER' => 3,   //总用户组
        'GROUP_ID_ADMIN' => 4,   //总管理员组

        'GROUP_ID_MERCHANT_SHOP' => 17,  //总商户组－商超组
        'GROUP_ID_MERCHANT_VEHICLE' => 18,  //总商户组－洗车组

        'GROUP_ID_MEMBER_CLIENT' => 29,  //总用户组下级顾客组
    ],
    /* 权限角色配置-数据库id值 */
    'AUTH_ROLE_ID' => [
        'ROLE_ID_MERCHANT_COMMITINFO' => 5,   //总商户组－提交资料角色
        'ROLE_ID_MERCHANT_SHOP_BOSS' => 4,   //总商户组－商超组－老板
        'ROLE_ID_MERCHANT_SHOP_MANAGER' => 21,  //总商户组－商超组－店长
        'ROLE_ID_MERCHANT_SHOP_STAFF' => 22,  //总商户组－商超组－员工
        'ROLE_ID_MERCHANT_VEHICLE_MANAGER' => 23,  //总商户组－洗车组－管理
        'ROLE_ID_MERCHANT_VEHICLE_WORKER' => 24,  //总商户组－洗车组－工人

        'ROLE_ID_MEMBER_CLIENT' => 1,   //顾客组下的普通用户角色
    ],

    /* 用户组配置 */
    'AUTH_GROUP_TYPE' => [
        'ADMIN' => 1,
        'MERCHANT' => 2,
        'MEMBER' => 3,
    ],

    /* 店铺TAG配置 */
    'SHOP_TAG' => [
        1 => '超市',
        2 => '生鲜',
        3 => '送水',
    ],

    /* 上传图片类型picture */
    'PICTURE_TYPE' => [
        'PRODUCT_PICTURE' => 1,    //产品图片
        'MERCHANT_SHOP_PICTURE' => 2,    //商家店铺图片
        'UCENTER_MEMBER_PHOTO' => 3,    //用户头像
        'CARWASH_MEMBER_PICTURE' => 4,    //洗车用户头像
        'CARWASH_MERCHANT_PICTURE' => 5,    //洗车工头像
    ],

    /* 软件包类型 */
    'VERSION_PACKAGE_TYPE' => [
        'MEMBER' => 1,    //apk用户包
        'MERCHANT' => 2,    //apk商家包
    ],

    /* 版本类型 */
    'VERSION_TYPE' => [
        'BASE' => 1,
        'ALPHA' => 2,
        'BETA' => 3,
        'RC' => 4,
        'RELEASE' => 5,
    ],

    /* 版本号：1.1.1.20150702_alpha */

    /* 支付宝配置 */
    'ALIPAY' => [
        'PARTNER' => '',//合作身份者id
        'SELLER_EMAIL' => '',//收款支付宝账号
        'KEY' => '',//安全检验码，以数字和字母组成的32位字符
        'SIGN_TYPE' => 'MD5',//签名方式
        'TRANSPORT' => 'https',//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    ],


    'VAR_SESSION_ID' => 'session_id',	//修复uploadify插件无法传递session_id的bug
    /* 文件上传相关配置 */
    'VERSION_PACKAGE_UPLOAD' => array(
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 50*1024*1024, //上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml,apk,pdf', //允许上传的文件后缀
        'autoSub'  => true, //自动子目录保存文件
        'subName'  => array('date', 'Y/m/d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/VersionPackage/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'  => '', //文件保存后缀，空则使用原后缀
        'replace'  => false, //存在同名是否覆盖
        'hash'     => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ), //下载模型上传配置（文件上传类配置）

    'VAR_SESSION_ID' => 'session_id',    //修复uploadify插件无法传递session_id的bug

    /* 图片上传相关配置 */
    'PRODUCT_PICTURE_UPLOAD' => [
        'picType' => 'PRODUCT_PICTURE',//ADD by wangjiang
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 2 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => ['date', 'Y/m/d'], //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/Product/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => ['uniqid', ''], //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ], //图片上传相关配置（文件上传类配置）
    'MERCHANT_SHOP_PICTURE_UPLOAD' => [
        'picType' => 'MERCHANT_SHOP_PICTURE',//ADD by wangjiang
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 2 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => ['date', 'Y/m/d'], //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/MerchantShop/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => ['uniqid', ''], //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ], //图片上传相关配置（文件上传类配置）
    'UCENTER_MEMBER_PICTURE_UPLOAD' => [
        'picType'=>'UCENTER_MEMBER_PHOTO',//ADD by wangjiang
        'mimes'    => '', //允许上传的文件MiMe类型
        'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
        'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub'  => true, //自动子目录保存文件
        'subName'  => array('date', 'Y/m/d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/UcenterMember/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => ['uniqid', ''], //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ], //图片上传相关配置（文件上传类配置）
    'CARWASH_MEMBER_PICTURE_UPLOAD' => [
        'picType' => 'CARWASH_MEMBER_PICTURE',//ADD by wangjiang
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 2 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => ['date', 'Y/m/d'], //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/CarwashMember/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => ['uniqid', ''], //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ], //图片上传相关配置（文件上传类配置）
    'CARWASH_MERCHANT_PICTURE_UPLOAD' => [
        'picType' => 'CARWASH_MERCHANT_PICTURE',//ADD by wangjiang
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 2 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => ['date', 'Y/m/d'], //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/CarwashMerchant/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => ['uniqid', ''], //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ], //图片上传相关配置（文件上传类配置）
    'FEEDBACK_PICTURE_UPLOAD' => [
        'picType' => 'FEEDBACK_PICTURE_PICTURE',//ADD by wangjiang
        'mimes' => '', //允许上传的文件MiMe类型
        'maxSize' => 2 * 1024 * 1024, //上传的文件大小限制 (0-不做限制)
        'exts' => 'jpg,gif,png,jpeg', //允许上传的文件后缀
        'autoSub' => true, //自动子目录保存文件
        'subName' => ['date', 'Y/m/d'], //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './Uploads/FeedBack/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => ['uniqid', ''], //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调函数，如果存在返回文件信息数组
    ], //图片上传相关配置（文件上传类配置）
    'PRODUCT_PICTURE_UPLOAD_DRIVER' => 'local',
    //本地上传文件驱动配置
    'UPLOAD_LOCAL_CONFIG' => [],

    //验证码过期时间，单位秒
    'VERIFY_CODE_EXPIRE' => 60,

    //系统自动查找洗车工参数
    'AUTO_MERCHANT_SCAN' => [
        //查找最大范围，单位米
        'RANGE' => 5000,
        //时间匹配参数，单位秒，洗车工最后的订单时间与用户指定的时间之差必须大于该值才能匹配
        'PRESET_TIME' => 1000
    ],

];

<?php
return array(

    /* URL配置 */
    'URL_CASE_INSENSITIVE' => true, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL' => 0, //URL模式
    'VAR_URL_PARAMS' => '', // PATHINFO URL参数变量
    'URL_PATHINFO_DEPR' => '/', //PATHINFO URL分割符

    /* 模板相关配置 ×××××××××开发使用××××××××× */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/images',
        '__CSS__'    => __ROOT__ . '/Public/' . MODULE_NAME . '/css',
        '__JS__'     => __ROOT__ . '/Public/' . MODULE_NAME . '/js',
    ),

    /* SESSION 和 COOKIE 配置 */
    'SESSION_PREFIX' => 'o2o_member',

    /* 数据缓存设置 */
    //'DATA_CACHE_PREFIX'    => 'o2o_member_', // 缓存前缀

    /* 后台错误页面模板 ×××××××××××开发使用×××××××××× */

    /* 异常配置 */
    'ERROR_PAGE' => __ROOT__.'/Public/exception.php',

    'APIMEM_ALLOW_ACCESS' => [
        'user/login',
        'user/register',
        'user/forgetPassword',
        'user/getVerifyCode',
        'product/getMerchantList',
        'product/getAppriseList',
        'product/getMerchantDetail',
        'product/getDepotCategory',
        'product/getBrand',
        'product/getProductList',
        'product/getShopProductList',
        'product/getProductDetail',
        'product/lists',
        'product/find',
        'merchant/getCarWashers',
        'merchant/get',
        'merchant/getAppriseList',
        'merchantdepot/getById',
        'region/getList',
        'region/lists',
        'region/tree',
        'region/treeToXml',
        'system/apkDownload',
        'Alipay/callback',
        'Alipay/createRsaSign',
    ],

    'APIMEM_WEB_ACCESS'  => [
        'Alipay/callback',
        'Alipay/createRsaSign',
    ],
);

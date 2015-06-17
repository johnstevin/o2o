<?php
/**
 * Created by PhpStorm.
 * User: biu
 * Date: 6/15/15
 * Time: 4:14 PM
 */
namespace Apimember\Controller;

use Common\Model\OrderModel;

require APP_PATH . 'Common/Vendor/alipay/alipay_core.function.php';
require APP_PATH . 'Common/Vendor/alipay/alipay_md5.function.php';
require APP_PATH . 'Common/Vendor/alipay/alipay_notify.class.php';
require APP_PATH . 'Common/Vendor/alipay/alipay_submit.class.php';

class AlipayController extends ApiController
{
    /**
     * HTTPS形式消息验证地址
     */
    public $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     */
    public $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    public static $alipayNotify;

    public function callback()
    {
        $config = [
            'partner' => C('ALIPAY.PARTNER'),//合作身份者id
            'seller_email' => C('ALIPAY.SELLER_EMAIL'),//收款支付宝账号
            'key' => C('ALIPAY.KEY'),//安全检验码，以数字和字母组成的32位字符
            'sign_type' => C('ALIPAY.SIGN_TYPE'),//签名方式
            'input_charset' => 'utf-8',//字符编码格式
            'cacert' => getcwd() . '\\cacert.pem',//ca证书路径地址，用于curl中ssl校验，请保证cacert.pem文件在当前文件夹目录中
            'transport' => C('ALIPAY.TRANSPORT')
        ];
        $alipayNotify = new \AlipayNotify($config);
        if (!$alipayNotify->verifyNotify()) {
            logResult('支付宝异步通知验证失败');
            exit('fail');
        }
        //商户订单号
        $out_trade_no = $_POST['out_trade_no'];
        $orderModel = OrderModel::getInstance();
        $order = $orderModel->get($out_trade_no);
        if ($order['pay_status'] == OrderModel::PAY_STATUS_TRUE) {
            exit('success');//如果订单的状态已经是已经支付，则直接告诉支付宝成功鸟
        }
        //判断交易状态
        if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {//普通接口在支付成功后的状态，高级接口在支付成功后会返回【TRADE_SUCCESS】，3个月后才会返回【TRADE_FINISHED】
            $ids = [];
            if (!empty($order['_childs'])) {
                $ids = array_map(function ($v) {
                    return $v['id'];
                }, $order['_childs']);
            }
            $ids[] = $order['id'];//无论是否有子订单，都把父级订单的ID加入
            if ($orderModel->where(['id' => ['IN', $ids]])->save(['pay_status' => OrderModel::PAY_STATUS_TRUE])) {
                //TODO 这儿要增加消息推送通知
                exit('success');//如果保存成功，则通知支付宝俺已经处理成功~\(≧▽≦)/~
            }
            exit('fail');
        }
    }
}
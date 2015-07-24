<?php
namespace Apimember\Controller;

use Common\Model\OrderModel;
use Common\Model\OrderVehicleModel;

require APP_PATH . '/Common/Vendor/alipay/alipay_notify.class.php';

/**
 * 支付宝控制器
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Apimember\Controller
 */
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
    public $merchantPrivateKeyPath = 'key/rsa_private_key.pem';
    public $alipayPublicKeyPath = 'key/alipay_public_key.pem';

    private $config = [];//支付宝配置信息

    public function _initialize()
    {
        parent::_initialize();
        $this->config = [
            'partner' => C('ALIPAY.PARTNER'),//合作身份者id
            'seller_email' => C('ALIPAY.SELLER_EMAIL'),//收款支付宝账号
            'key' => C('ALIPAY.KEY'),//安全检验码，以数字和字母组成的32位字符
            'sign_type' => C('ALIPAY.SIGN_TYPE'),//签名方式
            'input_charset' => 'utf-8',//字符编码格式
            'cacert' => getcwd() . '\\cacert.pem',//ca证书路径地址，用于curl中ssl校验，请保证cacert.pem文件在当前文件夹目录中
            'transport' => C('ALIPAY.TRANSPORT'),
            'private_key_path' => $this->merchantPrivateKeyPath,
            'ali_public_key_path' => $this->alipayPublicKeyPath
        ];
    }

    /**
     * 支付宝成功支付的回调接口
     * @author Fufeng Nie <niefufeng@gmail.com>
     */
    public function callback()
    {
        $alipayNotify = new \AlipayNotify($this->config);

        //如果通知验证失败
        if (!$alipayNotify->verifyNotify()) exit('fail');

        //如果是准备付款，就告诉支付宝我收到通知了，不往下走了
        if ($_POST['trade_status'] === 'WAIT_BUYER_PAY') exit('success');

        //商户订单号
        $out_trade_no = $_POST['out_trade_no'];
        //截取订单的第一位
        $first = strtoupper(substr(trim($out_trade_no), 0, 1));

        if ($first === 'S') {//如果第一位是S，那么就是商超订单
            $orderModel = OrderModel::getInstance();
            $order = $orderModel->getByCode($out_trade_no);
            if (empty($order)) {//如果订单未找到，直接告诉支付宝失败（因为可能是系统问题导致未找到）
                exit('fail');
            }
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
                if ($orderModel->where(['id' => ['IN', $ids]])->save(['pay_status' => OrderModel::PAY_STATUS_TRUE, 'reality_price' => $_POST['total_fee']])) {
                    $pushTitle = '订单已支付提醒';
                    if (empty($order['_childs'])) {
                        $pushContet = '用户于【' . date('Y-m-d H:i:s') . '】支付了您的订单【' . $order['order_code'] . '】';
                        $pushExtras = [
                            'action' => 'orderDetail',
                            'order_id' => $order['id']
                        ];
                        push_by_uid('STORE', get_shopkeeper_by_shopid($order['shop_id']), $pushContet, $pushExtras, $pushTitle);
                    } else {
                        foreach ($order['_childs'] as $child) {
                            $pushContet = '用户于【' . date('Y-m-d H:i:s') . '】支付了您的订单【' . $child['order_code'] . '】';
                            $pushExtras = [
                                'action' => 'orderDetail',
                                'order_id' => $child['id']
                            ];
                            push_by_uid('STORE', get_shopkeeper_by_shopid($child['shop_id']), $pushContet, $pushExtras, $pushTitle);
                        }
                    }
                    exit('success');//如果保存成功，则通知支付宝俺已经处理成功~\(≧▽≦)/~
                }
                exit('fail');
            }
        } else {//否则就是洗车订单
            if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {//普通接口在支付成功后的状态，高级接口在支付成功后会返回【TRADE_SUCCESS】，3个月后才会返回【TRADE_FINISHED】
                if (!$order = OrderVehicleModel::getInstance()->getByCode($out_trade_no)) exit('fail');
                if ($order['pay_status'] == 1) {
                    exit('success');
                }
                if (OrderVehicleModel::getInstance()->where(['id' => $order['id']])->save(['pay_status' => 1, 'status' => OrderVehicleModel::STATUS_CLOSED, 'reality_price' => $_POST['total_fee']])) {
                    $pushContet = '用户于【' . date('Y-m-d H:i:s') . '】支付了您的订单【' . $order['order_code'] . '】';
                    $pushTitle = '订单已支付提醒';
                    $pushExtras = [
                        'action' => 'VehicleOrderDetail',
                        'order_id' => $order['id']
                    ];
                    push_by_uid('STORE', get_shopkeeper_by_shopid($order['shop_id']), $pushContet, $pushExtras, $pushTitle);
                    exit('success');
                };
                exit('fail');
            }
        }
    }

    /**
     * 创建rsa签名
     * @author Fufeng Nie <niefufeng@gmail.com>
     */
    public function createRsaSign()
    {
        if (empty($_POST)) E('签名参数不能为空');

        $_POST['partner'] = $this->config['partner'];

        $_POST['seller_id'] = $this->config['seller_email'];

        if (empty($_POST['subject'])) {
            $_POST['subject'] = '一点社区订单收费';
        }

        $_POST['body'] = '一点社区订单收费';

        $_POST['service'] = 'mobile.securitypay.pay';

        $_POST['payment_type'] = '1';

        $_POST['_input_charset'] = 'utf-8';

        $_POST['it_b_pay'] = '30m';

        //除去待签名参数数组中的空值和签名参数
        $para_filter = paraFilter($_POST);

        //对待签名参数数组排序
        $para_sort = argSort($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = createLinkstring($para_sort);

        $sign = rsaSign($prestr, $this->merchantPrivateKeyPath);

        $this->apiSuccess(['data' => $sign]);
    }
}
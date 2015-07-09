<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;

use Common\Model\OrderModel;

/**
 * 订单控制器
 * @package Apimember\Controller
 */
class OrderController extends ApiController
{
    /**
     * 设置购物车,需要accesstoken
     * @author WangJiang
     * @return json
     * <pre>
     * 调用样例 POST  apimber.php?s=/order/setcart/userId/0
     * POST 数据：
     * [
     * {
     * "shop_id": 1,
     * "products":
     * [
     * {
     * "id": 12323,
     * "total": 2,
     * "product_id": 1
     * }
     * ]
     * }
     * ]
     * 返回
     *     {
     * "success": true,
     * "error_code": 0,
     * "message": "设置成功"
     * }</pre>
     */
    public function setCart()
    {
        try {
            if (IS_POST) {
                $uid = $this->getUserId();
                $data = json_decode(file_get_contents('php://input'));
                if (F("user/cart/$uid", $data) !== false)
                    $this->apiSuccess(null, '设置成功');
                else
                    E('设置购物车失败');
            } else
                E('非法调用');
        } catch (\Exception $ex) {
            $this->apiError(50010, $ex->getMessage());
        }
    }

    /**
     * 获得购物车,需要accesstoken
     * @author WangJiang
     * @return json
     * <pre>
     * 调用样例 GET apimber.php?s=/order/getcart/userId/0
     * 返回
     * [
     * {
     * "shop_id": 1,
     * "products":
     * [
     * {
     * "id": 12323,
     * "total": 2,
     * "product_id": 1
     * }
     * ]
     * }
     * ]</pre>
     */
    public function getCart()
    {
        try {
            $uid = $this->getUserId();
            $data = F("user/cart/$uid");
            if ($data !== false)
                $this->apiSuccess(['data' => $data]);
            else
                E('获得购物车失败');
        } catch (\Exception $ex) {
            $this->apiError(50011, $ex->getMessage());
        }
    }

    /**
     * ###获得订单列表,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $shopId 商铺ID
     * @param null|int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param null|int $deliveryMode 支付模式
     * @param bool $getShop 是否获取商铺信息，默认为否
     * @param bool $getUser 是否获取订单的购买者信息，默认为否
     * @param bool $getProducts 是否获取订单下的商品
     * ``` JSON
     * {
     *      "success": true,
     *      "error_code": 0,
     *      "data": [
     *           {
     *               "id": "1",
     *               "order_code": "abcdef",
     *               "pid": "0",
     *               "user_id": "1",
     *               "shop_id": "1",
     *               "price": "998.00",
     *               "status": "3",
     *               "remark": "remark",
     *               "pay_mode": "1",
     *               "delivery_mode": "1",
     *               "delivery_time": "99999999",
     *               "pay_status": "1",
     *               "deliveryman": "我是送货员",
     *               "add_time": "0",
     *               "add_ip": "0",
     *               "update_time": "1432867815",
     *               "update_ip": "127.0.0.1",
     *               "mobile": "10086",
     *               "address": "成都市武侯区外双楠",
     *               "consignee": "雷锋",
     *               "_childs": [
     *                   {
     *                   "id": "2",
     *                   "order_code": "a1",
     *                   "pid": "1",
     *                   "user_id": "1",
     *                   "shop_id": "1",
     *                   "price": "500.00",
     *                   "status": "3",
     *                   "remark": "remark",
     *                   "pay_mode": "1",
     *                   "delivery_mode": "1",
     *                   "delivery_time": "9999999",
     *                   "pay_status": "1",
     *                   "deliveryman": "我是送货员2",
     *                   "add_time": "0",
     *                   "add_ip": "0",
     *                   "update_time": "1432880144",
     *                   "update_ip": "127.0.0.1",
     *                   "mobile": "1008611",
     *                   "address": "鹭岛路",
     *                   "consignee": "雷锋"
     *                   },
     *               ],
     *       ],
     *       "pagination": "<div>  <span class=\"current\">1</span><a class=\"num\" href=\"/apimber.php?m=apimember&c=order&a=lists&p=2\">2</a> <a class=\"next\" href=\"/apimber.php?m=apimember&c=order&a=lists&p=2\">>></a> </div>"
     * }
     * ```
     */
    public function lists($status = null, $payStatus = null, $deliveryMode = null, $getProducts = true, $getShop = false, $getUser = false)
    {
        $lists = OrderModel::getInstance()->getLists(null, $this->getUserId(), $status, $payStatus, $deliveryMode, $getShop, $getUser, $getProducts)['data'];
        //我滴个神啊，那些做手机端开发的非要只取两条产品信息-_-!
        if ($getProducts) {
            foreach ($lists as &$data) {
                if (!empty($data['_products'])) {
                    $data['_products_total'] = count($data['_products']);
                } else {
                    foreach ($data['_childs'] as &$child) {
                        $child['_products_total'] = count($child['_products']);
                    }
                }
            }
        }
        $this->apiSuccess(['data' => $lists]);
    }

    /**
     * ###根据ID获取单个订单,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     * @param null|int $status 订单状态
     * @param bool $getChilds 是否获取子订单
     * @param bool $getProducts 是否获取订单的商品信息
     * @param bool $fields 要查询的订单的字段（不能限制商品信息字段）
     * ``` JSON
     * {
     *      "success": true,
     *      "error_code": 0,
     *      "data": {
     *          "id": "1",
     *          "pid": "0",
     *          "price": "998.00",
     *          "mobile": "10086",
     *          "remark": "remark",
     *          "status": "3",
     *          "user_id": "1",
     *          "pay_mode": "1",
     *          "consignee": "雷锋",
     *          "pay_status": "1",
     *          "order_code": "abcdef",
     *          "address": "成都市武侯区外双楠",
     *          "add_ip": "0",
     *          "add_time": "0",
     *          "update_ip": "127.0.0.1",
     *          "update_time": "1432867815",
     *          "deliveryman": "我是送货员",
     *          "delivery_mode": "1",
     *          "delivery_time": "99999999",
     *          "_childs": [
     *          {
     *              "id": "2",
     *              "order_code": "a1",
     *              "pid": "1",
     *              "user_id": "1",
     *              "shop_id": "1",
     *              "price": "500.00",
     *              "status": "3",
     *              "remark": "remark",
     *              "pay_mode": "1",
     *              "delivery_mode": "1",
     *              "delivery_time": "9999999",
     *              "pay_status": "1",
     *              "deliveryman": "我是送货员2",
     *              "add_time": "0",
     *              "add_ip": "0",
     *              "update_time": "1432880144",
     *              "update_ip": "127.0.0.1",
     *              "mobile": "1008611",
     *              "address": "鹭岛路",
     *              "consignee": "雷锋"
     *              },
     *          ]
     *      }
     * }
     * ```
     */
    public function find($id, $status = null, $getChilds = true, $getProducts = true, $fields = true)
    {
        $this->apiSuccess(['data' => OrderModel::get($id, $status, $fields, $getChilds, $getProducts)]);
    }

    /**
     * ###获取子订单列表,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $orderId 订单ID
     * @param string $fileds 要查询的字段
     * @param bool $getProducts 是否要查询订单的商品
     * @param null $status 订单状态
     * ``` JSON
     * {
     * "success": true,
     * "error_code": 0,
     * "data": [
     *      {
     *          "id": "2",
     *          "order_code": "a1",
     *          "pid": "1",
     *          "user_id": "1",
     *          "shop_id": "1",
     *          "price": "500.00",
     *          "status": "3",
     *          "remark": "remark",
     *          "pay_mode": "1",
     *          "delivery_mode": "1",
     *          "delivery_time": "9999999",
     *          "pay_status": "1",
     *          "deliveryman": "我是送货员2",
     *          "add_time": "0",
     *          "add_ip": "0",
     *          "update_time": "1432880144",
     *          "update_ip": "127.0.0.1",
     *          "mobile": "1008611",
     *          "address": "鹭岛路",
     *          "consignee": "雷锋"
     *      },
     * ]
     * }
     * ```
     */
    public function childLists($orderId, $fileds = '*', $getProducts = false, $status = null)
    {
        $this->apiSuccess(['data' => OrderModel::get($orderId, $status, $fileds, true, $getProducts)['_childs']]);
    }

    /**
     * ###提交订单,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $orderKey 预生成的订单（请调用initOrder）key
     * @param string $address 联系地址
     * @param string|int $mobile 联系方式
     * @param int $payMode 支付方式
     * @param string $consignee 收货人
     * @param string $remark 订单备注
     */
    public function submitOrder($orderKey, $address, $mobile, $payMode, $consignee, $remark = '')
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->submitOrder($this->getUserId(), $orderKey, $mobile, $consignee, $address, $remark, $payMode)]);
    }

    /**
     * ###更新订单信息,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     * @param json|array $cart 购物车
     * @param null|int $payMode 支付方式
     * @param null|int $deliveryMode 配送模式
     * @param null|int $deliveryTime 配送时间
     * @param null|int|string $mobile 收货人联系电话
     * @param null|string $address 收货地址
     * @param null|string $consignee 收货人
     */
    public function updateOrder($id, $cart = null, $payMode = null, $deliveryMode = null, $deliveryTime = null, $mobile = null, $address = null, $consignee = null)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->updateOrder($id, $cart, $payMode, $deliveryMode, $deliveryTime, $mobile, $address, $consignee)]);
    }

    /**
     * ###更新支付状态,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payStatus 要更新为哪个订单状态
     */
    public function updatePayStatus($id, $payStatus)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->updateOrderPayStatus($id, $payStatus)]);
    }

    /**
     * ###取消订单,需要accesstoken
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param string $content 取消订单的理由
     */
    public function cancelOrder($id, $content)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->CancelOrder($id, $content, $this->getUserId())]);
    }

    /**
     * ## 预处理订单，用于分配订单的商品到xx商家和计算价格之类的事情
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string|array|json $cart 【购物车】，可传json或者数组，每个子集都需包含depot_id、product_id、total和shop_id
     * @param int $deliveryMode 【配送方式】
     * @param int $deliveryTime 【配送时间】，时间戳
     * @param float $lng 【经度】
     * @param float $lat 【纬度】
     * @param bool $split 是否需要服务端拆单
     */
    public function initOrder($cart, $deliveryMode, $deliveryTime, $lng, $lat, $split = true)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->initOrder($cart, $deliveryMode, $deliveryTime, $lng, $lat, $split)]);
    }

    /**
     * ## 商家确认订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID，必传参数
     * @param bool $confirm 是否确认订单，可选参数，默认为确认
     */
    public function merchantConfirmOrder($id, $confirm = true)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->MerchantConfirmOrder($id, $confirm, $this->getUserId())]);
    }

    /**
     * ## 用户确认订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID，必传参数
     * @param bool $confirm 是否确认，可选参数，默认为确认
     * @param string $content 如果拒绝订单，请传拒绝的理由，否则请无视。。。
     */
    public function userConfirmOrder($id, $confirm = true, $content = '')
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->UserConfirmOrder($id, $confirm, $this->getUserId(), $content)]);
    }

    /**
     * ## 完成订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     */
    public function completeOrder($id)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->CompleteOrder($id, $this->getUserId())]);
    }

    /**
     * ## 删除订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     */
    public function deleteOrder($id)
    {
        $this->apiSuccess(['data' => OrderModel::getInstance()->DeleteOrder($id, $this->getUserId())]);
    }
}

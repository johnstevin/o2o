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
     * 设置购物车
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
                $uid=$this->getUserId();
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
     * 获得购物车
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
            $uid=$this->getUserId();
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
     * @ignore
     * 加入购物车
     * @param
     * @author  stevin
     */
    public function cartAdd()
    {

    }

    /**
     * @ignore
     * 我的购物车列表
     * @param
     * @author  stevin
     */
    public function cartList()
    {

    }

    /**
     * @ignore
     * 购物车删除
     * @param
     * @author  stevin
     */
    public function cartDel()
    {

    }

    /**
     * @ignore
     * 支付接口
     * @param
     * @author  stevin
     */
    public function pay()
    {

    }

    /**
     * @ignore
     * 订单处理
     * @param
     * @author  stevin
     */
    public function orderDo()
    {

    }

    /**
     * ###获得订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|int $shopId 商铺ID
     * @param null|int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param string|array $fields 要查询的字段
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
    public function lists($shopId = null, $userId = null, $status = null, $payStatus = null, $fields = '*', $getProducts = true)
    {
        $lists = OrderModel::getLists($shopId, $userId, $status, $payStatus, $fields, $getProducts);
        //我滴个神啊，那些做手机端开发的非要只取两条产品信息-_-!
        if ($getProducts) {
            foreach ($lists['data'] as &$data) {
                if (!empty($data['_products'])) {
                    $data['_products_total'] = count($data['_products']);
                    if ($data['_products_total'] > 2) {
                        $data['_products'] = array_slice($data['_products'], 0, 2);
                    }
                } else {
                    foreach ($data['_childs'] as &$child) {
                        $child['_products_total'] = count($child['_products']);
                        if ($child['_products_total'] > 2) {
                            $child['_products'] = array_slice($child['_products'], 0, 2);
                        }
                    }
                }
            }
        }
        $this->apiSuccess($lists);
    }

    /**
     * ###根据ID获取单个订单
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
    public function find($id, $status = null, $getChilds = false, $getProducts = false, $fields = true)
    {
        $this->apiSuccess(['data' => OrderModel::get($id, $status, $fields, $getChilds, $getProducts)]);
    }

    /**
     * ###获取子订单列表
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
     * ###提交订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param string|array $cart 购物车
     * @param string $address 联系地址
     * @param string|int $mobile 联系方式
     * @param int $payMode 支付方式
     * @param string $consignee 收货人
     * @param int $deliveryMode 配送方式
     * @param string $remark 订单备注
     * @param bool $split 是否需要系统拆单
     */
    public function submitOrder($userId, $cart, $address, $mobile, $payMode, $consignee, $deliveryMode, $remark = '', $split = false)
    {
        $this->apiSuccess(['data' => OrderModel::submitOrder($userId, $cart, $mobile, $consignee, $address, $remark, $payMode, $deliveryMode, $split)]);
    }

    /**
     * ###更新订单信息
     * @param int $id
     * @param null|int $payMode
     * @param null|int $deliveryMode
     * @param null|int $deliveryTime
     * @param null|int|string $mobile
     * @param null|string $address
     * @param null|string $consignee
     */
    public function updateOrder($id, $payMode = null, $deliveryMode = null, $deliveryTime = null, $mobile = null, $address = null, $consignee = null)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrder($id, $payMode, $deliveryMode, $deliveryTime, $mobile, $address, $consignee)]);
    }

    /**
     * ###更新订单状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     * @param int $status 要更新为哪个状态
     */
    public function updateStatus($id, $status)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderStatus($id, $status)]);
    }

    /**
     * ###更新订单支付方式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payMode 要更新为哪个支付方式
     */
    public function updatePayMode($id, $payMode)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderPayMode($id, $payMode)]);
    }

    /**
     * ###更新支付状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payStatus 要更新为哪个订单状态
     */
    public function updatePayStatus($id, $payStatus)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderPayStatus($id, $payStatus)]);
    }

    /**
     * ###取消订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     */
    public function cancelOrder($id)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderStatus($id, OrderModel::STATUS_CANCEL)]);
    }
}

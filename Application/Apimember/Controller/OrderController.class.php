<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;

use Common\Model\OrderModel;

/**
 * 订单
 * Class OrderController
 * @package Api\Controller
 */
class OrderController extends ApiController
{

    /**
     * 加入购物车
     * @param
     * @author  stevin
     */
    public function cartAdd()
    {

    }

    /**
     * 我的购物车列表
     * @param
     * @author  stevin
     */
    public function cartList()
    {

    }

    /**
     * 购物车删除
     * @param
     * @author  stevin
     */
    public function cartDel()
    {

    }

    /**
     * 支付接口
     * @param
     * @author  stevin
     */
    public function pay()
    {

    }

    /**
     * 订单处理
     * @param
     * @author  stevin
     */
    public function orderDo()
    {

    }

    /**
     * 获得订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|int $shopId 商铺ID
     * @param null|int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param string|array $fields 要查询的字段
     * @param bool $getProducts 是否获取订单下的商品
     */
    public function lists($shopId = null, $userId = null, $status = null, $payStatus = null, $fields = '*', $getProducts = false)
    {
        $this->apiSuccess(OrderModel::getLists($shopId, $userId, $status, $payStatus, $fields, $getProducts));
    }

    /**
     * 根据ID获取单个订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     * @param null|int $status 订单状态
     * @param bool $getChilds 是否获取子订单
     * @param bool $getProducts 是否获取订单的商品信息
     * @param bool $fields 要查询的订单的字段（不能限制商品信息字段）
     */
    public function find($id, $status = null, $getChilds = false, $getProducts = false, $fields = true)
    {
        $this->apiSuccess(['data' => OrderModel::get($id, $status, $fields, $getChilds, $getProducts)]);
    }

    /**
     * 获取子订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $orderId 订单ID
     * @param string $fileds 要查询的字段
     * @param bool $getProducts 是否要查询订单的商品
     * @param null $status 订单状态
     */
    public function childLists($orderId, $fileds = '*', $getProducts = false, $status = null)
    {
        $this->apiSuccess(OrderModel::get($orderId, $status, $fileds, false, $getProducts)['_childs']);
    }

    /**
     * 提交订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param string|array $cart
     * @param $address
     * @param $mobile
     * @param $payMode
     * @param $consignee
     * @param $deliveryMode
     * @param string $remark
     * @param bool $split
     */
    public function submitOrder($userId, $cart, $address, $mobile, $payMode, $consignee, $deliveryMode, $remark = '', $split = false)
    {
        $this->apiSuccess(['data' => OrderModel::submitOrder($userId, $cart, $mobile, $consignee, $address, $remark, $payMode, $deliveryMode, $split)]);
    }

    /**
     * 更新订单信息
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
     * 更新订单状态
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
     * 更新订单支付方式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payMode 要更新为哪个支付方式
     */
    public function updatePayMode($id, $payMode)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderPayMode($id, $payMode)]);
    }

    /**
     * 更新支付状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payStatus 要更新为哪个订单状态
     */
    public function updatePayStatus($id, $payStatus)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderPayStatus($id, $payStatus)]);
    }

    /**
     * 取消订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     */
    public function cancelOrder($id)
    {
        $this->apiSuccess(['data' => OrderModel::updateOrderStatus($id, OrderModel::STATUS_CANCEL)]);
    }
}
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
}
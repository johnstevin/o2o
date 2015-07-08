<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimerchant\Controller;

use Common\Model\OrderModel;
/**
 * 商家订单管理
 * Class OrderController
 * @package Api\Controller
 */
class OrderController extends ApiController {

    /**
     * 订单列表
     * @param
     * @author  stevin
     */
    public function getOrderList(){

    }

    /**
     * 订单详细
     * @param
     * @author  stevin
     */
    public function getOrderDetail(){

    }

    /**
     * 订单处理
     * @param
     * @author  stevin
     */
    public function getOrderDo(){

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

}
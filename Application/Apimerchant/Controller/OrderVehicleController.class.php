<?php
/**
 * Created by PhpStorm.
 * User: wangjiang
 * Date: 6/23/15
 * Time: 4:43 PM
 */

namespace Apimerchant\Controller;


use Common\Model\OrderVehicleModel;

class OrderVehicleController extends ApiController{

    /**
     * 员工查看分配给自己的订单,需要accesstoken
     * @param null $status （0-未分配，1-已分配，2-已接单，3-处理中，4-处理完，5-订单结束，6订单取消）
     * @param int $page 页码，从1开始
     * @param int $pageSize，页大小
     * @return json
     * ``` json
     * {
     *      "success":<>,
     *      "error_code":<>,
     *      "message":<>,
     *      "data":[
     *          {
     *              {
     *                  "lnglat":[经度,纬度],
     *                  "id": "<订单ID>",
     *                  "order_code": "<订单编号>",
     *                  "user_id": "<用户ID>",
     *                  "shop_id": "<商铺ID>",
     *                  "status": "<状态，0-未分配，1-已分配，2-已接单，3-处理中，4-处理完，5-订单结束，6订单取消>",
     *                  "worker_id": "<洗车工ID，为空表示还没有分配>",
     *                  "address": "<车辆地址>",
     *                  "car_number": "<车牌号>",
     *                  "price": "<洗车价格>",
     *                  "user_picture_ids": "<用户上传的图片，多个用[,]隔开>",
     *                  "worder_picture_ids": "<洗车工上传的图片，多个用[,]隔开>",
     *                  "add_time": "<新增时间>",
     *                  "update_time": "<修改时间>"
     *              },...
     *          }
     *      ]
     * }
     * ```
     * <pre>调用样例 GET apimchant.php?s=OrderVehicle/getSelfList&accesstoken=104</pre>
     * ``` json
     *{
     *
     *      "success": true,
     *      "error_code": 0,
     *      "message": "",
     *      "data":[
     *          {
     *              "lnglat":
     *              [
     *                  120,
     *                  30
     *              ],
     *              "id": "20806",
     *              "order_code": "1000",
     *              "user_id": "104",
     *              "shop_id": "0",
     *              "status": "5",
     *              "worker_id": "10168",
     *              "address": "",
     *              "car_number": "A12345",
     *              "price": "15.00",
     *              "user_picture_ids": "",
     *              "worder_picture_ids": "",
     *              "add_time": "1388505600",
     *              "update_time": "1388505600"
     *
     *          },
     *          {
     *          "lnglat":
     *          [
     *              120,
     *              30
     *          ],
     *          "id": "20811",
     *          "order_code": "1000",
     *          "user_id": "104",
     *          "shop_id": "0",
     *          "status": "0",
     *          "worker_id": "10146",
     *          "address": "",
     *          "car_number": "A12345",
     *          "price": "15.00",
     *          "user_picture_ids": "",
     *          "worder_picture_ids": "",
     *          "add_time": "1388592000",
     *          "update_time": "1388592000"
     *          }
     *      ]
     * }
     * ```
     */
    public function getSelfList($status = null,$page = 1, $pageSize = 10){
        try {
            $pageSize > 50 and $pageSize = 50;
            $page--;
            $page *= $pageSize;
            $uid = $this->getUserId();

            $where['worker_id']=$uid;

            if(!is_null($status))
                $where['status']=$status;

            $m = D('OrderVehicle');
            $data = $m
                ->field(['st_astext(lnglat) as lnglat',
                    'id',
                    'order_code',
                    'user_id',
                    'shop_id',
                    'status',
                    'worker_id',
                    'address',
                    'car_number',
                    'price',
                    'user_picture_ids',
                    'worder_picture_ids',
                    'add_time',
                    'update_time',
                ])
                ->where($where)->limit($page, $pageSize)->select();

            $this->apiSuccess(['data' => $data], '');
        } catch (\Exception $ex) {
            $this->apiError(51102, $ex->getMessage());
        }
    }

    /**
     * <pre>
     * 洗车工接受订单，订单状态转换成已接单,POST数据，需要accesstoken
     * int orderId 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function accept(){
        try {
            if(!IS_POST)
                E('非法调用，请用POST命令');
            $uid=$this->getUserId();
            $oid=I('post.orderId');
            $m=new OrderVehicleModel();
            $order=$m->find($oid);
            if($order['worker_id']!=$uid)
                E('用户无权修改该订单');
            unset($order['lnglat']);//该字段在这里保存会报错
            $order['status']=OrderVehicleModel::STATUS_CONFIRM;
            $this->apiSuccess(null, '操作成功');
        } catch (\Exception $ex) {
            $this->apiError(51103, $ex->getMessage());
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/19/15
 * Time: 2:20 PM
 */

namespace Apimember\Controller;


use Common\Model\MerchantModel;
use Common\Model\OrderVehicleModel;

class OrderVehicleController extends ApiController
{
    /**
     * 获得用户订单,需要accesstoken
     * @author WangJiang
     * @param null $status （0-未分配，1-已分配，2-已接单，3-处理中，4-处理完，5-订单结束，6订单取消）
     * @param null $orderCode 订单号
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
     * <pre>调用样例 GET apimber.php?s=OrderVehicle/getList&accesstoken=104</pre>
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
    public function getList($status = null, $orderCode = null, $page = 1, $pageSize = 10)
    {
        try {
            $pageSize > 50 and $pageSize = 50;
            $page--;
            $page *= $pageSize;
            $uid = $this->getUserId();

            $where['user_id']=$uid;

            if(!is_null($status))
                $where['status']=$status;
            if(!is_null($orderCode))
                $where['order_code']=$orderCode;
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

            //print_r($m->getLastSql());die;

            $this->apiSuccess(['data' => $data], '');
        } catch (\Exception $ex) {
            $this->apiError(51002, $ex->getMessage());
        }
    }

    /**
     * 创建订单，POST参数
     * <pre>
     * 参数：
     * worker_id 洗车工ID
     * address 车辆地址，必须
     * lnglat  车辆位置，格式为'lng lat'，必须
     * preset_time 预定时间，绝对时间戳，必须
     * car_number 车牌号，必须
     * user_picture_ids  用户照片ID，用','隔开，必须
     * </pre>
     * @author WangJiang
     * @return json
     */
    public function create(){
        /*
         * 洗车下单流程：
         *  if 设置worker_id then 将status设置为已分配，插入记录，向worker_id推送消息
         *  else 很据lnglat计算周围空闲worker_id
         *    if 找到了 then 选择第一个，将status设置为已分配，插入记录，向worker_id推送消息
         *    else 将status设置为未分配，插入记录
         */
        try {
            if (IS_POST) {
                $model = new OrderVehicleModel();
                if (!($data=$model->create()))
                    E('参数传递失败');

                $data['user_id']=$this->getUserId();
                if(!array_key_exists('worker_id',$data) or empty($data['worker_id'])){
                    list($lng,$lat)=explode(' ',$data['lnglat']);
                    $wid=(new MerchantModel())->getAvailableWorker($lng,$lat,$data['preset_time']);
                    if(is_null($wid))
                        $data['status']=OrderVehicleModel::STATUS_NO_WORKER;
                    else{
                        $data['status']=OrderVehicleModel::STATUS_HAS_WORKER;
                        $data['worker_id']=$wid;
                    }
                }else
                    $data['status']=OrderVehicleModel::STATUS_HAS_WORKER;

                //判断是否推送消息
                $sendMsg=$data['status']==OrderVehicleModel::STATUS_HAS_WORKER;

                $model->data($data);
                $this->apiSuccess(['id' => intval($model->insert(function() use ($sendMsg,$wid){
                    if($sendMsg){
                        //TODO 实现消息推送$wid
                    }
                }))],'');
            } else
                E('非法调用，请用POST调用');
        } catch (\Exception $ex) {
            $this->apiError(51021, $ex->getMessage());
        }
    }

    /**
     * 取消订单，POST参数
     * <pre>
     * 参数
     * orderId 订单ID，必须
     * </pre>
     * @author WangJiang
     * @return json
     */
    public function cancel(){
        try{
            if(!IS_POST)
                E('非法调用，请用POST调用');
            $oid=I('post.orderId');
            $m=new OrderVehicleModel();
            $m->userCancel($oid,$this->getUserId());
            $this->apiSuccess(null,'成功');
        }catch (\Exception $ex) {
            $this->apiError(51022, $ex->getMessage());
        }
    }

    //TODO 等APP端开始再讨论细节
    /**
     * 付费，POST参数
     * <pre>
     * 参数
     * orderId 订单ID，必须
     * charge 费用
     * grade1 评分1
     * grade2 评分2
     * grade3 评分3
     * content 评价
     * </pre>
     * @author WangJiang
     * @return json
     */
//    public function pay(){
//        try{
//            if(!IS_POST)
//                E('非法调用，请用POST调用');
//            $oid=I('post.orderId');
//            $charge=I('post.charge',0);
//            $grade1=I('post.grade1',0);
//            $grade2=I('post.grade2',0);
//            $grade3=I('post.grade3',0);
//            $content=I('post.content','');
//
//            if($charge<15 and strlen($content)<25)
//                E('请给出不少于25字的评价，谢谢。');
//
//            $m=new OrderVehicleModel();
//            $m->find($oid);
//
//            D()->startTrans();
//            try{
//                $m->status=OrderVehicleModel::STATUS_CLOSED;
//                $m->save();
//
//                D()->commit();
//            }catch (\Exception $ex){
//                D()->rollback();
//                throw $ex;
//            }
//            $this->apiSuccess(null,'成功');
//        }catch (\Exception $ex) {
//            $this->apiError(51023, $ex->getMessage());
//        }
//    }
}

<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/19/15
 * Time: 2:20 PM
 */

namespace Apimember\Controller;


use Common\Model\AppraiseModel;
use Common\Model\MerchantModel;
use Common\Model\MerchantShopModel;
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
     *                  "worder_pictures": ["url"...],
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
     *              "worder_pictures": [],
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
     *          "worder_pictures": [],
     *          "add_time": "1388592000",
     *          "update_time": "1388592000"
     *          }
     *      ]
     * }
     * ```
     */
    public function getList($status = null,$payStatus=null, $orderCode = null, $page = 1, $pageSize = 10)
    {
        try {
            $uid = $this->getUserId();
            //print_r($m->getLastSql());die;
            $this->apiSuccess(['data' => (new OrderVehicleModel())->getUserList($uid,$status,$payStatus,$orderCode,$page,$pageSize)], '');
        } catch (\Exception $ex) {
            $this->apiError(51002, $ex->getMessage());
        }
    }

    /**
     * 创建订单，POST参数
     * <pre>
     * 参数：
     * worker_id 洗车工ID
     * shop_id 商店id
     * address 车辆地址，必须
     * street_number 车辆地址门牌号，必须
     * car_number 车牌号，必须
     * consignee 车主姓名，必须
     * mobile 车主电话，必须
     * lnglat  车辆位置，格式为'lng lat'，必须
     * preset_time 预定时间，绝对时间戳，必须
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

                //print_r($model);die;

                if (!($data=$model->create()))
                    E('参数传递失败,'.$model->getError());

                //print_r(json_encode($data));die;

                $data['user_id']=$this->getUserId();
                if(!array_key_exists('worker_id',$data) or empty($data['worker_id'])){
                    list($lng,$lat)=explode(' ',$data['lnglat']);
                    $worker=(new MerchantModel())->getAvailableWorker($lng,$lat,$data['preset_time']);

                    if(empty($worker))
                        E('没有找到合适的服务人员');


                    $data['status']=OrderVehicleModel::STATUS_HAS_WORKER;
                    $data['worker_id']=$worker[0]['id'];
                    $data['shop_id']=$worker[0]['shop_id'];

                }else
                    $data['status']=OrderVehicleModel::STATUS_HAS_WORKER;

                $wid=$data['worker_id'];

                //判断是否推送消息
                $sendMsg=$data['status']==OrderVehicleModel::STATUS_HAS_WORKER;

                $model->data($data);

                $newId=intval($model->insert(function() use ($sendMsg,$wid){
                    if($sendMsg){
                        //TODO 实现消息推送$wid
                    }
                }));

                push_by_uid('STORE',$data['worker_id'],'您有新订单，请及时处理',[
                    'action'=>'vehicleOrderDetail',
                    'order_id'=>$newId,
                ],'您有新的订单');

                action_log('api_create_order_veh', $model, $newId, UID,3);

                $this->apiSuccess(['data'=>['id' => $newId]],'');
            } else
                E('非法调用，请用POST调用');
        } catch (\Exception $ex) {
            $this->apiError(51021, $ex->getMessage());
        }
    }

    /**
     * 根据地址查找责任公司
     * @param $province
     * @param $city
     * @param $district
     */
    private function _get_car_wash_shop($province,$city,$district){
        $shop=D('MerchantShop')
            ->where(' group_id in (select group_id from sq_auth_group_region where region_id in (select id from sq_region where name=:city))')
            ->bind([':city'=>$city])
            ->find();
        return $shop ? $shop['id'] : null;
    }

    /**
     * 取消订单，POST参数
     * <pre>
     * 参数
     * id 订单ID，必须
     * </pre>
     * @author WangJiang
     * @return json
     */
    public function cancel(){
        try{
            if(!IS_POST)
                E('非法调用，请用POST调用');
            $oid=I('post.id');
            $m=new OrderVehicleModel();
            $m->userCancel($oid,$this->getUserId());
            action_log('api_cancel_order_veh', $m, $oid, UID,3);
            $this->apiSuccess(['data'=>[]],'成功');
        }catch (\Exception $ex) {
            $this->apiError(51022, $ex->getMessage());
        }
    }

    /**
     * 评价，POST参数
     * <pre>
     * 参数
     * orderId 订单ID，必须
     * charge 费用
     * grade1 评分1
     * grade2 评分2
     * grade3 评分3
     * content 评价
     * anonymity 是否匿名　０－不是，１－是
     * </pre>
     * @author WangJiang
     * @return json
     */
    public function appraise(){
        try{
            if(!IS_POST)
                E('非法调用，请用POST调用');
            $oid=I('post.orderId');
            $grade1=I('post.grade1',0);
            $grade2=I('post.grade2',0);
            $grade3=I('post.grade3',0);
            $content=I('post.content');
            $anonymity=I('post.anonymity',0);
            if(empty($content))
                $content='该用户很深沉，什么也没说。';

            $m=new OrderVehicleModel();
            $data=$m->find($oid);

            D()->startTrans();
            try{
                $m->save(['id'=>$oid,'status'=>OrderVehicleModel::STATUS_CLOSED]);

                D('Appraise')->add([
                    'order_id'=>$oid,
                    'shop_id'=>$data['shop_id'],
                    'user_id'=>$this->getUserId(),
                    'merchant_id'=>$data['worker_id'],
                    'grade_1'=>$grade1,
                    'grade_2'=>$grade2,
                    'grade_3'=>$grade3,
                    'content'=>$content,
                    'anonymity'=>$anonymity,
                ]);

                D()->commit();

                /*用户取消订单消息推送*/
                push_by_uid('STORE',$data['worker_id'],'用户评价了订单',[
                    'action'=>'vehicleOrderDetail',
                    'order_id'=>$oid
                ],'用户评价了订单');

                $this->apiSuccess(['data'=>[]],'成功');
            }catch (\Exception $ex){
                D()->rollback();
                throw $ex;
            }

        }catch (\Exception $ex) {
            $this->apiError(51023, $ex->getMessage());
        }
    }
}

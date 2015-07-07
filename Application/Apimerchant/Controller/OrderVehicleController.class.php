<?php
/**
 * Created by PhpStorm.
 * User: wangjiang
 * Date: 6/23/15
 * Time: 4:43 PM
 */

namespace Apimerchant\Controller;


use Common\Model\MerchantModel;
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
     *                  "user_pictures": ["url"...],
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
     *              "user_pictures": [],
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
     *          "user_pictures": [],
     *          "add_time": "1388592000",
     *          "update_time": "1388592000"
     *          }
     *      ]
     * }
     * ```
     */
    public function getSelfList($status = null,$page = 1, $pageSize = 10){
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
                'ifnull(user_picture_ids,\'\') as user_picture_ids',
                'add_time',
                'update_time',
            ])
            ->where($where)
            ->limit($page, $pageSize)->select();

        foreach($data as &$i){
            $i['user_picture_paths']=[];
            foreach(D('Picture')
                        ->field(['path'])
                        ->where(['id'=>['in',$i['user_picture_ids']]])
                        ->select() as $p){
                $i['user_picture_paths'][]=$p['path'];
            }
            unset($i['user_picture_ids']);
        }

        $this->apiSuccess(['data' => $data], '');
    }

    /**
     * 管理员获得订单列表
     * @author WangJiang
     * @param null $status 指定订单状态
     * @param int $page 页码
     * @param int $pageSize 页大小
     * @return json
     */
    public function getList($status = null,$page = 1, $pageSize = 10){
        $pageSize > 50 and $pageSize = 50;
//        $page--;
//        $page *= $pageSize;
        $mgrRoleId=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $gids=$this->getUserGroupIds($mgrRoleId,true);//获得管理分组
        $data=D('OrderVehicle')
            ->join('inner join sq_merchant_shop on sq_merchant_shop.group_id in (:groupIds)')
            ->where('shop_id in (select id from sq_merchant_shop where group_id in (:groupIds))')
            ->bind([':groupIds'=>implode(',',$gids)])
            ->page($page,$pageSize)
            //->fetchSql()
            ->select();
        //var_dump($data);die;
        $this->apiSuccess(['data' => $data], '');
    }

    /**
     * <pre>
     * 洗车工修改订单状态,POST数据，需要accesstoken
     * int orderId 订单ID、必须
     * int status 订单状态、必须
     * </pre>
     * @author WangJiang
     */
    public function chaneStatus(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.orderId');
        $status=I('post.status');
        (new OrderVehicleModel())->workerChaneStatus($oid,$uid,$status);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * <pre>
     * 洗车工接受订单，订单状态转换成已接单,POST数据，需要accesstoken
     * int orderId 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function accept(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.orderId');
        (new OrderVehicleModel())->workerChaneStatus($oid,$uid,OrderVehicleModel::STATUS_CONFIRM);
        $this->apiSuccess(['data'=>[]], '操作成功');
        //TODO 消息推送
    }

    /**
     * <pre>
     * 洗车工开始处理订单，订单状态转换成开始处理,POST数据，需要accesstoken
     * int orderId 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function start(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.orderId');
        (new OrderVehicleModel())->workerChaneStatus($oid,$uid,OrderVehicleModel::STATUS_TREATING);
        $this->apiSuccess(['data'=>[]], '操作成功');
        //TODO 消息推送
    }

    /**
     * <pre>
     * 洗车工处理完毕，订单状态转换成处理完毕,POST数据，需要accesstoken
     * int orderId 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function end(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.orderId');
        (new OrderVehicleModel())->workerChaneStatus($oid,$uid,OrderVehicleModel::STATUS_DONE);
        $this->apiSuccess(['data'=>[]], '操作成功');
        //TODO 消息推送
    }

    /**
     * <pre>
     * 洗车工拒绝订单，一种情况，系统自动排单，洗车工发现不属于自己负责地区，可以选择拒绝,POST数据，需要accesstoken
     * int orderId 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function reject(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.orderId');
        (new OrderVehicleModel())->workerChaneStatus($oid,$uid,OrderVehicleModel::STATUS_NO_WORKER);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * 管理员修改订单数据,POST数据，需要accesstoken
     * <pre>
     * id 订单ID
     * address string 新地址，可选
     * lnglat  "lng lat" 新经纬度，可选
     * preset_time int 新服务时间，可选
     * car_number string 新车牌 可选
     * </pre>
     * @author WangJiang
     */
    public function update(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $model=D('OrderVehicle');
        if (!($data=$model->create()))
            E('参数传递失败');

        $id=I('post.id');//为了避免传递参数时混淆，强制指定post

        $mgrRoleId=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $groupIds=$this->getUserGroupIds($mgrRoleId,true);
        $order=$model->find($id);
        if(empty($order))
            E('订单不存在');
        $shop=D('MerchantShop')->find($order['shop_id']);
        if(!in_array($shop['group_id'],$groupIds))
            E('用户无权修改此订单');

        //var_dump($data);
        $model->data($data);
        $model->update($id);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    public function reassign(){
        if(!IS_POST)
            E('非法调用，请用POST命令');

        $id=I('post.id');//为了避免传递参数时混淆，强制指定post

        $mgrRoleId=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $groupIds=$this->getUserGroupIds($mgrRoleId,true);

        $model=D('OrderVehicle');
        $order=$model->field(['st_astext(lnglat) as lnglat','preset_time'])->find($id);
        //var_dump($order);die;
        if(empty($order))
            E('订单不存在');
        $shop=D('MerchantShop')->find($order['shop_id']);
        if(!in_array($shop['group_id'],$groupIds))
            E('用户无权修改此订单');

        $worker=(new MerchantModel())->getAvailableWorker($order['lnglat'][0],$order['lnglat'][1],$order['preset_time']);
        if(empty($worker))
            E('没有找到合适的服务人员');

        $model->save(['id'=>$id,'worker_id'=>$worker['id']]);

        //TODO 实现消息推送$wid

        $this->apiSuccess(['data'=>[]], '操作成功');
    }
}

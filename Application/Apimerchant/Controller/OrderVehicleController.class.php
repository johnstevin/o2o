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
    public function getSelfList($status = null,$payStatus = null,$page = 1, $pageSize = 10){
        $pageSize > 50 and $pageSize = 50;

        $uid = $this->getUserId();

        $where['worker_id']=$uid;
        $where['status']=array('GT',OrderVehicleModel::STATUS_NO_WORKER);

        if(!is_null($status))
            $where['status']=$status;
        if(!is_null($payStatus))
            $where['sq_order_vehicle.pay_status']=$payStatus;

        $m = D('OrderVehicle');
        $data = $m
            ->field(['st_astext(lnglat) as lnglat',
                'id',
                'order_code',
                'user_id',
                'shop_id',
                'status',
                'pay_status',
                'worker_id',
                'address',
                'street_number',
                'car_number',
                'price',
                'ifnull(user_picture_ids,\'\') as user_picture_ids',
                'ifnull(worder_picture_ids,\'\') as worder_picture_ids',
                'add_time',
                'update_time',
                'preset_time',
                'add_time',
            ])
            ->where($where)
            ->order('update_time desc,add_time desc')
            ->page($page, $pageSize)->select();

        foreach($data as &$i){
//            foreach(D('Picture')
//                        ->field(['path'])
//                        ->where(['id'=>['in',$i['user_picture_ids']]])
//                        ->select() as $p){
//                $i['user_pictures'][]=$p['path'];
//            }


            $i['user_pictures']=array_column(D('Picture')->field(['path'])
                        ->where(['id'=>['in',$i['user_picture_ids']]])
                        ->select(),'path');

            unset($i['user_picture_ids']);

            $i['worker_pictures']=array_column(D('Picture')
                        ->field(['path'])
                        ->where(['id'=>['in',$i['worder_picture_ids']]])
                        ->select(),'path');

            unset($i['worder_picture_ids']);

            $user=D('UcenterMember')
                ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
                ->where(['sq_ucenter_member.id'=>$i['user_id']])
                ->find();
            $i['user_name']=$user['real_name']?$user['real_name']:"";
            $i['user_picture']=$user['path']?$user['path']:"";
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
    public function getList($status = null,$payStatus=null,$page = 1, $pageSize = 10){
        $pageSize > 50 and $pageSize = 50;
        $mgrRoleId=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $gids=$this->getUserGroupIds($mgrRoleId,true);//获得管理分组
        $gids=implode(',',$gids);

        $where['_string']='shop_id in (select id from sq_merchant_shop where group_id in ('.$gids.'))';
        if(!is_null($status)){
            if(is_array($status))
                $where['sq_order_vehicle.status']=['in',$status];
            else
                $where['sq_order_vehicle.status']=$status;
        }
        if(!is_null($payStatus))
            $where['sq_order_vehicle.pay_status']=$payStatus;

        $data=D('OrderVehicle')
            //->join('inner join sq_merchant_shop on sq_merchant_shop.group_id in ('.$gids.')')
            ->field([
                'st_astext(lnglat) as lnglat',
                'id',
                'order_code',
                'user_id',
                'shop_id',
                'status',
                'pay_status',
                'worker_id',
                'address',
                'street_number',
                'car_number',
                'preset_time',
                'mobile',
                'price',
                'ifnull(user_picture_ids,\'\') as user_picture_ids',
                'ifnull(worder_picture_ids,\'\') as worder_picture_ids',
                'add_time',
                'update_time',
            ])
            ->where($where)
            ->page($page,$pageSize)
          //  ->fetchSql()
            ->select();
        //var_dump($data);die;

        foreach($data as &$i){
            $i['user_pictures']= array_column(D('Picture')
                        ->field(['path'])
                        ->where(['id'=>['in',$i['user_picture_ids']]])
                        ->select(),'path');
            unset($i['user_picture_ids']);

            $i['worker_pictures']=array_column(D('Picture')
                        ->field(['path'])
                        ->where(['id'=>['in',$i['worder_picture_ids']]])
                        ->select(), 'path');
            unset($i['worder_picture_ids']);

            $user=D('UcenterMember')
                ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
                ->where(['sq_ucenter_member.id'=>$i['user_id']])
                ->find();
            $i['user_name']=$user['real_name'];
            $i['user_picture']=$user['path']?$user['path']:"";

            $user=D('UcenterMember')
                ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
                ->where(['sq_ucenter_member.id'=>$i['worker_id']])
                ->find();
            $i['worker_name']=$user['real_name'];
            $i['worker_picture']=$user['path']?$user['path']:"";
        }
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
     * int id 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function accept(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.id');
        (new OrderVehicleModel())->accept($oid,$uid);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * <pre>
     * 洗车工开始处理订单，订单状态转换成开始处理,POST数据，需要accesstoken
     * int id 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function start(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.id');
        (new OrderVehicleModel())->start($oid,$uid);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * <pre>
     * 洗车工处理完毕，订单状态转换成处理完毕,POST数据，需要accesstoken
     * int id 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function end(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.id');
        (new OrderVehicleModel())->end($oid,$uid);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * <pre>
     * 洗车工拒绝订单，一种情况，系统自动排单，洗车工发现不属于自己负责地区，可以选择拒绝,POST数据，需要accesstoken
     * int id 订单ID、必须
     * </pre>
     * @author WangJiang
     */
    public function reject(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $uid=$this->getUserId();
        $oid=I('post.id');
        (new OrderVehicleModel())->reject($oid,$uid);
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * 管理员修改订单数据,POST数据，需要accesstoken
     * <pre>
     * id 订单ID，必须
     * address string 新地址
     * street_number 车辆地址门牌
     * lnglat  "lng lat" 新经纬度
     * preset_time int 新服务时间
     * car_number string 新车牌
     * remark 说明
     * </pre>
     * @author WangJiang
     */
    public function update(){
        if(!IS_POST)
            E('非法调用，请用POST命令');
        $this->__update();
        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * 管理员重新分配订单,POST数据，需要accesstoken
     * <pre>
     * id 订单ID，必须
     * remark 说明
     * </pre>
     * @author WangJiang
     */
    public function reassign(){
        if(!IS_POST)
            E('非法调用，请用POST命令');

        $this->__update();

        $id=I('post.id');//为了避免传递参数时混淆，强制指定post

        $mgrRoleId=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $groupIds=$this->getUserGroupIds($mgrRoleId,true);

        $model=D('OrderVehicle');
        $order=$model->field(['st_astext(lnglat) as lnglat','preset_time','shop_id'])->find($id);
        //var_dump($order);die;
        if(empty($order))
            E('订单不存在');
        $shop=D('MerchantShop')->find($order['shop_id']);
//        var_dump($order);var_dump($shop);var_dump($groupIds);die;
        if(!in_array($shop['group_id'],$groupIds))
            E('用户无权修改此订单');

        $worker=(new MerchantModel())->getAvailableWorker($order['lnglat'][0],$order['lnglat'][1],$order['preset_time']);
        if(empty($worker))
            E('没有找到合适的服务人员');

        $model->save(['id'=>$id,'worker_id'=>$worker[0]['id'],'shop_id'=>$worker[0]['shop_id'],'status'=>OrderVehicleModel::STATUS_HAS_WORKER]);

        action_log('api_reassign_order_veh', $model, $id, UID,2);

        push_by_uid('CLIENT',$order['user_id'],'管理员重新分配了您的订单',[
            'action'=>'vehicleOrderDetail',
            'order_id'=>$id
        ],'管理员重新分配了您的订单');

        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * 管理员取消订单,POST数据，需要accesstoken
     * <pre>
     * id 订单ID，必须
     * remark 说明
     * </pre>
     * @author WangJiang
     */
    public function cancel(){
        if(!IS_POST)
            E('非法调用，请用POST命令');

        $id=I('post.id');//为了避免传递参数时混淆，强制指定post
        $remark=I('post.remark');

        $mgrRoleId=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $groupIds=$this->getUserGroupIds($mgrRoleId,true);

        (new OrderVehicleModel())->managerCancel($this->getUserId(),$id,$remark,$groupIds);

        $this->apiSuccess(['data'=>[]], '操作成功');
    }

    /**
     * 管理员修改订单信息
     */
    private function __update()
    {
        $model = D('OrderVehicle');
        if (!($data = $model->create()))
            E('参数传递失败' . $model->getError());

        $id = I('post.id');//为了避免传递参数时混淆，强制指定post

        $mgrRoleId = C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//管理角色
        $groupIds = $this->getUserGroupIds($mgrRoleId, true);
        $order = $model->find($id);
        if (empty($order))
            E('订单不存在');
        $shop = D('MerchantShop')->find($order['shop_id']);
        if (!in_array($shop['group_id'], $groupIds))
            E('用户无权修改此订单');

        //var_dump($data);
        $filter = ['address', 'car_number', 'preset_time', 'lnglat'];
        foreach ($data as $k => $v) {
            if (!in_array($k, $filter))
                unset($data[$k]);
        }
        $model->data($data);
        if ($model->update($id))
            action_log('api_update_order_veh', $model, $id, UID, 2);
    }


    /**
     * 商家获取某个订单的详细信息
     * @param null $id 订单id
     */
    public function vehicleOrderDetail($id=null){

        if(is_null($id)||!is_numeric($id)||$id==0)
            E('参数非法');

        //$this->getUserId();

        $data = D('OrderVehicle')->MerchantOrderDetail($id);
        $this->apiSuccess(['data' => empty($data)?"[[]]":[$data]], '');
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/19/15
 * Time: 2:27 PM
 */

namespace Common\Model;


use Think\Model\AdvModel;

class OrderVehicleModel extends AdvModel
{
    /**
     * @var self
     */
    private static $instance;
    #订单状态（0-未分配，1-已分配，2-已接单，3-处理中，4-处理完，5-订单结束，6订单取消）'
    const STATUS_NO_WORKER = 0;
    const STATUS_HAS_WORKER = 1;
    const STATUS_CONFIRM = 2;
    const STATUS_TREATING = 3;
    const STATUS_DONE = 4;
    const STATUS_CLOSED = 5;
    const STATUS_CANCELED = 6;

    /*支付状态*/
    const PAY_STATUS_TRUE = 1;//已支付
    const PAY_STATUS_FALSE = 0;//未支付


    /*评价状态*/
    const APPRAISE_CLOSED = -1;
    const APPRAISE_END = 1;


    protected $pk = 'id';
    protected $autoinc = true;

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return \Common\Model\OrderVehicleModel
     */
    public static function getInstance()
    {
        return self::$instance instanceof self ? self::$instance : self::$instance = new self;
    }

    protected $fields = [
        'id',
        'order_code',
        'user_id',
        'shop_id',
        'status',
        'worker_id',
        'address',
        'street_number',
        'lnglat',
        'preset_time',
        'car_number',
        'price',
        'user_picture_ids',
        'worder_picture_ids',
        'add_time',
        'add_ip',
        'update_time',
        'update_ip',
        'consignee',
        'mobile',
        'pay_status',
        '_type' => [
            'id' => 'int',
            'order_code' => 'string',
            'user_id' => 'int',
            'shop_id' => 'int',
            'status' => 'int',
            'worker_id' => 'int',
            'address' => 'string',
            'street_number' => 'string',
            'lnglat' => 'point',
            'preset_time' => 'int',
            'car_number' => 'string',
            'price' => 'float',
            'user_picture_ids' => 'string',
            'worder_picture_ids' => 'string',
            'add_time' => 'int',
            'add_ip' => 'int',
            'update_time' => 'int',
            'update_ip' => 'int',
            'consignee' => 'string',
            'mobile' => 'string',
            'pay_status' => 'int'
        ]
    ];

    /**
     * 自动完成
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $_auto = [
        [
            'add_time',
            'time',
            self::MODEL_INSERT,
            'function'
        ],
        [
            'add_ip',
            'get_client_ip',
            self::MODEL_INSERT,
            'function'
        ],
        [
            'update_time',
            'time',
            self::MODEL_BOTH,
            'function'
        ],
        [
            'update_ip',
            'get_client_ip_to_int',
            self::MODEL_UPDATE,
            'function'
        ],
        [
            'order_code',
            'create_vehide_order_code',
            self::MODEL_INSERT,
            'function'
        ]
    ];

    protected $_validate = [
        [
            'price',
            'currency',
            '价格格式错误',
            [self::EXISTS_VALIDATE,
                self::MODEL_BOTH]
        ],
        [
            'address',
            'require',
            '地址不能为空',
            [self::MUST_VALIDATE,
                self::MODEL_INSERT]
        ],
        [
            'consignee',
            'require',
            '车主姓名不能为空',
            [self::MUST_VALIDATE,
                self::MODEL_INSERT]
        ],
        [
            'mobile',
            'require',
            '车主手机不能为空',
            [self::MUST_VALIDATE,
                self::MODEL_INSERT]
        ],
        [
            'lnglat',
            'require',
            '坐标不能为空',
            [self::MUST_VALIDATE,
                self::MODEL_INSERT]
        ],
        [
            'preset_time',
            'require',
            '预定时间不能为空',
            [self::MUST_VALIDATE,
                self::MODEL_INSERT]
        ],
        [
            'status',
            [
                self::STATUS_NO_WORKER,
                self:: STATUS_HAS_WORKER,
                self:: STATUS_CONFIRM,
                self:: STATUS_TREATING,
                self:: STATUS_DONE,
                self:: STATUS_CLOSED,
                self:: STATUS_CANCELED,
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'order_code',
            'unique',
            '订单代码已经存在',
            self::EXISTS_VALIDATE
        ],
        [
            'user_id',
            'check_user_exist',
            '用户ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ]
    ];

    protected $readonlyField = [
        'id',
        'user_id',
        'add_time',
        'add_ip',
        'order_code',
    ];

    protected function _after_find(&$result, $options = '')
    {
        parent::_after_select($result, $options);
        $this->_after_query_row($result);
        //echo '<pre>';
        //print_r($result);
    }

    /**
     * 处理point类型字段值
     * @param $resultSet
     * @param string $options
     */
    protected function _after_select(&$resultSet, $options)
    {
        parent::_after_select($resultSet, $options);
        foreach ($resultSet as &$row) {
            $this->_after_query_row($row);
        }
    }

    /**
     * @param $row
     * @param $v
     * @return array
     */
    protected function _after_query_row(&$row)
    {
        foreach ($row as $k => &$v) {
            $type = $this->fields['_type'][$k];
            if ($type == 'point') {
                $lls = substr($v, 6, strlen($v) - 7);
                $ll = explode(' ', $lls);
                $v = [floatval($ll[0]), floatval($ll[1])];
            }
        }
    }

    /**
     * 插入一条记录
     * @author WangJiang
     * @param $success 成功回调
     */
    public function insert($success)
    {

        $data = $this->data();

        $bind = [];
        $orderVals = [];
        $orderFlds = [];
        foreach ($data as $key => $val) {
            if (!in_array($key, $this->fields, true)) {
                unset($data[$key]);
                continue;
            }
            $bindName = ":$key";
            if ($this->fields['_type'][$key] == 'point') {
                $orderVals[] = "st_geomfromtext($bindName)";
                $bind[$bindName] = "POINT($val)";
                $orderFlds[] = $key;
            } else {
                $orderVals[] = $bindName;
                $bind[$bindName] = $val;
                $orderFlds[] = $key;
            }
        }

        return do_transaction([
            ['sql' => 'INSERT INTO sq_order_vehicle(' . implode(',', $orderFlds) . ') VALUES(' . implode(',', $orderVals) . ');',
                'bind' => $bind,
                'newId' => true]
        ]);
    }

    public function update($id)
    {

        $data = $this->data();

        //检查状态变化是否合法
        $order = $this->find($id);
        if (isset($data['status']))
            $this->_assert_new_status($order['status'], $data['status']);

        $bind = [];
        $sets = [];
        foreach ($data as $key => $val) {
            if ($key == $this->pk)
                continue;
            if (!in_array($key, $this->fields, true)) {
                unset($data[$key]);
                continue;
            }
            $bindName = ":$key";
            if ($this->fields['_type'][$key] == 'point') {
                $bind[$bindName] = "POINT($val)";
                $sets[] = $key . '=' . "st_geomfromtext($bindName)";
            } else {
                $bind[$bindName] = $val;
                $sets[] = $key . '=' . $bindName;
            }
        }

        //var_dump($bind);die;

        if (empty($sets))
            return false;

        $bind[':id'] = $id;
        return do_transaction([
            ['sql' => 'UPDATE sq_order_vehicle set ' . implode(',', $sets) . ' where id=:id;',
                'bind' => $bind,
                'newId' => false]
        ]);

    }

    /**
     * 用户取消订单
     * @author WangJiang
     * @param $oid
     * @param $uid
     */
    public function userCancel($oid, $uid)
    {
        $data = $this->find($oid);

        //print_r($data);die;
        //echo json_encode(['user_id'=>$data['user_id'],'uid'=>$uid,'oid'=>$oid]);die;
        $this->_assert_new_status($data['status'], self::STATUS_CANCELED);
        //var_dump($data['user_id']);var_dump($uid);var_dump($data['user_id']!=intval($uid));die;
        if ($data['user_id'] != $uid)
            E('非本人操作');
        $ovs = D('OrderVehicleStatus');

        $this->startTrans();
        try {
            $this->save(['id' => $data['id'], 'status' => self::STATUS_CANCELED]);
            if (!$ovs->create([
                'order_id' => $oid,
                'user_id' => $uid,//$order['user_id'],
                'merchant_id'=>$data['worker_id'],
                'shop_id' => $data['shop_id'],
                'status' => self::STATUS_CANCELED,
                'content' => '用户取消订单',
            ])
            )
                E('参数传递失败 ' . $ovs->getError());
            $ovs->add();
            $this->commit();
            if ($data['status'] !== self::STATUS_CANCELED) {
                /*订单消息推送*/
                push_by_uid('STORE', $data['worker_id'], '您的订单【' . $data['order_code'] . '】被用户取消了，点击查看原因...', [
                    'action' => 'vehicleOrderDetail',
                    'order_id' => $oid
                ], '用户取消了订单');
            } else {

                //TODO 推送给管理员
            }
        } catch (\Exception $ex) {
            $this->rollback();
        }
    }

    /**
     * 管理员取消了订单
     * @param $uid
     * @param $id
     * @param $remark
     * @param $groupIds
     */
    public function managerCancel($uid, $id, $remark, $groupIds)
    {

        //$model = D('OrderVehicle');
        $order = $this
            //->field(['st_astext(lnglat) as lnglat','preset_time'])
            ->find($id);
        //var_dump($order);die;
        if (empty($order))
            E('订单不存在');
        $shop = D('MerchantShop')->find($order['shop_id']);
        if (!in_array($shop['group_id'], $groupIds))
            E('用户无权修改此订单');
        if (!in_array($order['status'], [0, 1, 2]))
            E('订单已经不能取消');
        $ovs = D('OrderVehicleStatus');

        $this->startTrans();
        try {
            $this->save(['id' => $id, 'status' => self::STATUS_CANCELED]);
            if (!$ovs->create([
                'order_id' => $id,
                'user_id'=>$order['user_id'],
                'merchant_id' => $uid,
                'shop_id' => $order['shop_id'],
                'status' => self::STATUS_CANCELED,
                'content' => $remark ? $remark : '经理取消订单',
            ])
            )
                E('参数传递失败 ' . $ovs->getError());

            $ovs->add();
            $this->commit();
        } catch (\Exception $ex) {
            $this->rollback();
            E($ex->getMessage());
        }

        /*用户取消订单消息推送*/
        push_by_uid('CLIENT', $order['user_id'], '取消的原因' . $remark, [
            'action' => 'vehicleOrderDetail',
            'order_id' => $id
        ], '您的订单被取消');
    }

    public function managerReassign($uid,$id,$remark,$groupIds){


        //$model=D('OrderVehicle');
        $order=$this->field(['st_astext(lnglat) as lnglat','preset_time','shop_id','user_id','status'])->find($id);
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


        $ovs = D('OrderVehicleStatus');
        M()->startTrans();
        try {
            $this->save(['id'=>$id,'worker_id'=>$worker[0]['id'],'shop_id'=>$worker[0]['shop_id'],'status'=>self::STATUS_HAS_WORKER]);

            if (!$ovs->create([
                'order_id' => $id,
                'user_id' => $order['user_id'],
                'merchant_id' => $uid,
                'shop_id' => $worker[0]['shop_id'],
                'status' => self::STATUS_HAS_WORKER,
                'content' => '管理员重新分配了订单， 附说明：' . $remark,
            ]))
                E('参数传递失败');


            $ovs->add();
            M()->commit();
        } catch (\Exception $ex) {
            M()->rollback();
            E($ex->getMessage());
        }


        action_log('api_reassign_order_veh', 'OrderVehicle', $id, UID,2);
        try {
        push_by_uid('CLIENT',$order['user_id'],"管理员重新分配了您的订单,附说明:$remark",[
            'action'=>'vehicleOrderDetail',
            'order_id'=>$id
        ],'管理员重新分配了您的订单');
        } catch (\Exception $ex) {
            /*不抛异常*/
            return;
        }
    }

    private static function _get_status_chain()
    {
        return [
            ['id' => 0, [1, 6]],
            ['id' => 1, [2, 6, 0]],
            ['id' => 2, [3]],
            ['id' => 3, [4]],
            ['id' => 4, [5]]
        ];
    }

    /**
     * @param $oldStatus
     * @param $newStatus
     */
    private function _assert_new_status($oldStatus, $newStatus)
    {
        foreach (self::_get_status_chain() as $i) {
            if ($i['id'] == $oldStatus) {
                if (!in_array($newStatus, $i[0]))
                    E('当前状态不能改变为指定状态');
                return;
            }
        }
        E('当前状态不能改变');
    }

    /**
     * * 洗车工接受订单
     * @param $oid
     * @param $uid
     */
    public function accept($oid, $uid)
    {


        $status = self::STATUS_CONFIRM;

        $data = $this->find($oid);
        if ($data['worker_id'] != $uid)
            E('非本人操作');
        $this->_assert_new_status($data['status'], $status);

        $ovs = new OrderVehicleStatusModel();

        $this->startTrans();
        try {

            $this->save(['id' => $data['id'], 'status' => $status]);

            if (!$ovs->create([
                'order_id' => $oid,
                'user_id' => $data['user_id'],
                'merchant_id' => $uid,
                'shop_id' => $data['shop_id'],
                'status' => $status,
                'content' => '服务人员受理该订单',
            ])
            )
                E('参数传递失败 ' . $ovs->getError());

            $ovs->add();
            $this->commit();
        } catch (\Exception $ex) {
            $this->rollback();
        }
        /*订单消息推送*/
        push_by_uid('CLIENT', $data['user_id'], '您的订单【' . $data['order_code'] . '】已被洁车师接单', [
            'action' => 'vehicleOrderDetail',
            'order_id' => $oid
        ], "您的订单已被接单");
    }

    /**
     * 洗车工开始处理订单，订单状态转换成开始处理
     * @param $oid
     * @param $uid
     */
    public function start($oid, $uid)
    {

        $status = self::STATUS_TREATING;
        $data = $this->find($oid);
        if ($data['worker_id'] != $uid)
            E('非本人操作');
        $this->_assert_new_status($data['status'], $status);

        $ovs = new OrderVehicleStatusModel();

        $this->startTrans();
        try {

            $this->save(['id' => $data['id'], 'status' => $status]);

            if (!$ovs->create([
                'order_id' => $oid,
                'user_id' => $data['user_id'],
                'merchant_id' => $uid,
                'shop_id' => $data['shop_id'],
                'status' => $status,
                'content' => '服务人员开始处理订单',
            ])
            )
                E('参数传递失败 ' . $ovs->getError());


            $ovs->add();
            $this->commit();
        } catch (\Exception $ex) {
            $this->rollback();
        }

        /*订单消息推送*/
        push_by_uid('CLIENT', $data['user_id'], '您的订单【' . $data['order_code'] . '】已开始处理', [
            'action' => 'vehicleOrderDetail',
            'order_id' => $oid
        ], "洁车师开始处理您的订单");

    }


    /**
     * 洗车工开始处理订单，订单状态转换成开始处理
     * @param $oid
     * @param $uid
     */
    public function end($oid, $uid)
    {

        $status = self::STATUS_DONE;

        $data = $this->find($oid);
        if ($data['worker_id'] != $uid)
            E('非本人操作');
        $this->_assert_new_status($data['status'], $status);

        $ovs = new OrderVehicleStatusModel();


        /*图片上传*/
        $type = 'CARWASH_MERCHANT';

        $photoinfos = upload_picture($uid, $type);

        $worder_picture_ids = array_column($photoinfos, 'id');

        $worder_picture_ids = is_array($worder_picture_ids) ? implode(',', $worder_picture_ids) : trim($worder_picture_ids, ',');

        $this->startTrans();
        try {
            $this->save(['id' => $data['id'], 'status' => $status, 'worder_picture_ids' => $worder_picture_ids]);

            //var_dump($ovs->getError());die;
            if (!$ovs->create([
                'order_id' => $oid,
                'user_id' => $data['user_id'],
                'merchant_id' => $uid,
                'shop_id' => $data['shop_id'],
                'status' => $status,
                'content' => '订单已被处理完毕',
            ])
            )
                E('参数传递失败 ' . $ovs->getError());


            $ovs->add();
            $this->commit();
        } catch (\Exception $ex) {
            $this->rollback();
        }


        /*订单消息推送*/
        push_by_uid('CLIENT', $data['user_id'], '您的订单【' . $data['order_code'] . '】已处理完毕', [
            'action' => 'vehicleOrderDetail',
            'order_id' => $oid
        ], "您的订单已处理完毕");

    }


    /**
     * 洗车工取消了订单
     * @param $oid
     * @param $uid
     */
    public function reject($oid, $uid)
    {

        $reason = I('post.reason');

        if (empty($reason))
            E("请填写原因");

        $status = self::STATUS_NO_WORKER;

        $data = $this->find($oid);
        if ($data['worker_id'] != $uid)
            E('非本人操作');
        $this->_assert_new_status($data['status'], $status);

        $ovs = new OrderVehicleStatusModel();

        $this->startTrans();
        try {
            $this->save(['id' => $data['id'], 'status' => $status]);

            if (!$ovs->create([
                'order_id' => $oid,
                'user_id' => $data['user_id'],
                'merchant_id' => $uid,
                'shop_id' => $data['shop_id'],
                'status' => $status,
                'content' => '服务人员取消了订单，原因：' . $reason,
            ])
            )
                E('参数传递失败 ' . $ovs->getError());


            $ovs->add();
            $this->commit();
        } catch (\Exception $ex) {
            $this->rollback();
        }

        /*订单消息推送*/
        push_by_uid('CLIENT', $data['user_id'], '您的订单【' . $data['order_code'] . '】特殊原因被洁车师取消了，请您及时关注', [
            'action' => 'vehicleOrderDetail',
            'order_id' => $oid
        ], "您的订单未能帮你处理");


        $group_id = M('MerchantShop')->where(['id' => $data['shop_id']])->getField('group_id');

        $mgrRoleId = C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');//店长角色


        $mgruid = M('AuthAccess')->field('uid')->where(['group_id' => $group_id, 'role_id' => $mgrRoleId, 'status' => 1])->select();


        if (!empty($mgruid)) {
            /*订单消息推送*/
            try {
                push_by_uid('STORE', $mgruid[0]['uid'], '订单【' . $data['order_code'] . '】被服务人员取消', [
                    'action' => 'vehicleOrderDetail',
                    'order_id' => $oid
                ], "有一个订单被服务人员取消");
                return;
            } catch (\Exception $ex) {
                //TODO 报异常：设备未找到
                return;
            }
        }
    }


    /**
     * 用户获取洗车订单列表
     * @param $uid
     * @param $status
     * @param $payStatus
     * @param $orderCode
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getUserList($uid, $status, $payStatus, $orderCode, $page, $pageSize)
    {
        $pageSize > 50 and $pageSize = 50;
        $where['sq_order_vehicle.user_id'] = $uid;

        if (!is_null($status)) {
            if (is_array($status))
                $where['sq_order_vehicle.status'] = ['in', $status];
            else
                $where['sq_order_vehicle.status'] = $status;
        }
        if (!is_null($payStatus))
            $where['sq_order_vehicle.pay_status'] = $payStatus;
        if (!is_null($orderCode))
            $where['sq_order_vehicle.order_code'] = $orderCode;
        $data = $this
            ->join('left join sq_merchant_shop on sq_merchant_shop.id=sq_order_vehicle.shop_id')
            ->join('left join sq_picture on sq_picture.id=sq_merchant_shop.picture')
            //->join('left join sq_ucenter_member on sq_ucenter_member.id=sq_order_vehicle.user_id')
            ->field(['st_astext(sq_order_vehicle.lnglat) as lnglat',
                'sq_order_vehicle.id',
                'ifnull(sq_merchant_shop.title,\'\') as shop_title',
                'ifnull(sq_merchant_shop.phone_number,\'\') as shop_phone_number',
                'ifnull(sq_picture.path,\'\') as shop_photo',
                'sq_order_vehicle.order_code',
                'sq_order_vehicle.user_id',
                //'ifnull(sq_ucenter_member.real_name,\'\') as user_name',
                'sq_order_vehicle.shop_id',
                'sq_order_vehicle.status',
                'sq_order_vehicle.pay_status',
                'sq_order_vehicle.worker_id',
                'sq_order_vehicle.address',
                'sq_order_vehicle.car_number',
                'sq_order_vehicle.price',
                'ifnull(worder_picture_ids,\'\') as worder_picture_ids',
                'ifnull(user_picture_ids,\'\') as user_picture_ids',
                'sq_order_vehicle.add_time',
                'sq_order_vehicle.update_time',
                'sq_order_vehicle.preset_time',
                'consignee',
                'mobile',
                'sq_order_vehicle.street_number'
            ])
            ->where($where)
            ->page($page, $pageSize)
            ->order('sq_order_vehicle.update_time desc')
            ->select();

        foreach ($data as &$i) {
            $i['worker_pictures'] = [];
            foreach (D('Picture')
                         ->field(['path'])
                         ->where(['id' => ['in', $i['worder_picture_ids']]])
                         ->select() as $p) {
                $i['worker_pictures'][] = $p['path'];
            }
            unset($i['worder_picture_ids']);
            $i['user_pictures'] = [];
            foreach (D('Picture')
                         ->field(['path'])
                         ->where(['id' => ['in', $i['user_picture_ids']]])
                         ->select() as $p) {
                $i['user_pictures'][] = $p['path'];
            }
            unset($i['user_picture_ids']);

            $user = D('UcenterMember')
                ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
                ->where(['sq_ucenter_member.id' => $i['worker_id']])
                ->find();
            $i['worker_name'] = $user['real_name'] ? $user['real_name'] : '';
            $i['worker_photo'] = $user['path'] ? $user['path'] : '';
        }

        return $data;
    }


    /**
     * 根据order_code获取订单信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $code 订单代码
     * @param bool|false $getShop 是否关联获取商铺信息
     * @param bool|false $getUser 是否获取用户信息
     * @return mixed
     */
    public function getByCode($code, $getShop = false, $getUser = false)
    {
        $code = trim($code);
        if ($code === '') E('订单code非法');
        $model = self::getInstance();
        $fields = [
            'ov.id',
            'ov.order_code',
            'ov.user_id',
            'ov.shop_id',
            'ov.status',
            'ov.worker_id',
            'ov.address',
            'astext(ov.lnglat) lnglat',
            'ov.car_number',
            'ov.price',
            'ov.user_picture_ids',
            'ov.worder_picture_ids',
            'ov.add_time',
            'ov.update_time',
            'ov.preset_time',
            'ov.pay_status'
        ];
        if ($getShop) {
            $fields = array_merge($fields, [
                'shop.id _shop_id',
                'shop.title _shop_title',
                'shop.phone _shop_phone',
                'shop.address _shop_address',
            ]);
            $model->join('LEFT JOIN sq_merchant_shop shop ON ov.shop_id=shop.id');
        }
        if ($getUser) {
            $fields = array_merge($fields, [
                'user.nickname _user_nickname',
                'user.sex _user_sex',
                'user.birthday _user_birthday'
            ]);
            $model->join('LEFT JOIN sq_member user ON user.uid=ov.user_id');
        }
        $data = $model->alias('ov')->field($fields)->where(['ov.order_code' => $code])->find();
        if ($getShop) {
            $data['_shop'] = [
                'id' => $data['_shop_id'],
                'title' => $data['_shop_title'],
                'phone' => $data['_shop_phone'],
                'address' => $data['_shop_address'],
            ];
            unset($data['_shop_id'], $data['_shop_address'], $data['_shop_phone'], $data['_shop_title']);
        }
        if ($getUser) {
            $data['_user'] = [
                'nickname' => $data['_user_nickname'],
                'sex' => $data['_user_sex'],
                'birthday' => $data['_user_birthday']
            ];
            unset($data['_user_nickname'], $data['_user_birthday'], $data['_user_sex']);
        }
        return $data;
    }


    /**
     * 商家获取某个订单详情
     * @param $id
     * @return mixed|string
     */
    public function MerchantOrderDetail($id)
    {

        //$this->getUserId();

        $data = $this
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
                'mobile',
            ])
            ->where(['id' => $id])
            ->find();

        if (empty($data)) {
            return "[]";
        }

        $OrderVehicleStatus = M('OrderVehicleStatus')->field('id,update_time,content')->where(['order_id' => $id, 'status' => $this::STATUS_NO_WORKER])->order('update_time desc')->select();

        $reason = $OrderVehicleStatus[0]['content'];
        $data['reason'] = empty($reason)||$data['status'] != self::STATUS_NO_WORKER ? "" : $reason;

        $data['user_pictures'] = array_column(D('Picture')->field(['path'])
            ->where(['id' => ['in', $data['user_picture_ids']]])
            ->select(), 'path');

        unset($data['user_picture_ids']);

        $data['worker_pictures'] = array_column(D('Picture')
            ->field(['path'])
            ->where(['id' => ['in', $data['worder_picture_ids']]])
            ->select(), 'path');

        unset($data['worder_picture_ids']);

        $user = D('UcenterMember')
            ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
            ->where(['sq_ucenter_member.id' => $data['user_id']])
            ->find();
        $data['user_name'] = $user['real_name'] ? $user['real_name'] : "";
        $data['user_picture'] = $user['path'] ? $user['path'] : "";

        return empty($data) ? "[]" : $data;
    }

    /**
     * 用户获取订单信息
     * @param $id
     * @return mixed|string
     */
    public function MemberOrderDetail($id)
    {
        $data = $this
            ->join('left join sq_merchant_shop on sq_merchant_shop.id=sq_order_vehicle.shop_id')
            ->join('left join sq_picture on sq_picture.id=sq_merchant_shop.picture')
            //->join('left join sq_ucenter_member on sq_ucenter_member.id=sq_order_vehicle.user_id')
            ->field(['st_astext(sq_order_vehicle.lnglat) as lnglat',
                'sq_order_vehicle.id',
                'ifnull(sq_merchant_shop.title,\'\') as shop_title',
                'ifnull(sq_merchant_shop.phone_number,\'\') as shop_phone_number',
                'ifnull(sq_picture.path,\'\') as shop_photo',
                'sq_order_vehicle.order_code',
                'sq_order_vehicle.user_id',
                //'ifnull(sq_ucenter_member.real_name,\'\') as user_name',
                'sq_order_vehicle.shop_id',
                'sq_order_vehicle.status',
                'sq_order_vehicle.pay_status',
                'sq_order_vehicle.worker_id',
                'sq_order_vehicle.address',
                'sq_order_vehicle.car_number',
                'sq_order_vehicle.price',
                'ifnull(worder_picture_ids,\'\') as worder_picture_ids',
                'ifnull(user_picture_ids,\'\') as user_picture_ids',
                'sq_order_vehicle.add_time',
                'sq_order_vehicle.update_time',
                'sq_order_vehicle.preset_time',
                'consignee',
                'mobile',
                'sq_order_vehicle.street_number'
            ])
            ->where(['sq_order_vehicle.id' => $id])
            ->find();

        if (empty($data)) {
            return "[]";
        }
        $OrderVehicleStatus = M('OrderVehicleStatus')->field('id,update_time,content')->where(['order_id' => $id, 'status' => self::STATUS_NO_WORKER])->order('update_time desc')->select();

        $reason = $OrderVehicleStatus[0]['content'];
        $data['reason'] = empty($reason)||$data['status'] != self::STATUS_NO_WORKER ? "" : $reason;

        $data['worker_pictures'] = [];
        foreach (D('Picture')
                     ->field(['path'])
                     ->where(['id' => ['in', $data['worder_picture_ids']]])
                     ->select() as $p) {
            $data['worker_pictures'][] = $p['path'];
        }
        unset($data['worder_picture_ids']);
        $data['user_pictures'] = [];
        foreach (D('Picture')
                     ->field(['path'])
                     ->where(['id' => ['in', $data['user_picture_ids']]])
                     ->select() as $p) {
            $data['user_pictures'][] = $p['path'];
        }
        unset($data['user_picture_ids']);

        $user = D('UcenterMember')
            ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
            ->where(['sq_ucenter_member.id' => $data['worker_id']])
            ->find();
        $data['worker_name'] = $user['real_name'] ? $user['real_name'] : '';
        $data['worker_photo'] = $user['path'] ? $user['path'] : '';

        return empty($data) ? "[]" : $data;
    }
}
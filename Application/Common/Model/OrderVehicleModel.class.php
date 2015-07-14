<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/19/15
 * Time: 2:27 PM
 */

namespace Common\Model;


use Think\Model\AdvModel;

class OrderVehicleModel extends AdvModel{
    #订单状态（0-未分配，1-已分配，2-已接单，3-处理中，4-处理完，5-订单结束，6订单取消）'
    const STATUS_NO_WORKER = 0;
    const STATUS_HAS_WORKER = 1;
    const STATUS_CONFIRM = 2;
    const STATUS_TREATING = 3;
    const STATUS_DONE = 4;
    const STATUS_CLOSED = 5;
    const STATUS_CANCELED = 6;

    protected $pk     = 'id';

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
        '_type' => [
            'id'=>'int',
            'order_code'=>'string',
            'user_id'=>'int',
            'shop_id'=>'int',
            'status'=>'int',
            'worker_id'=>'int',
            'address'=>'string',
            'street_number'=>'string',
            'lnglat'=>'point',
            'preset_time'=>'int',
            'car_number'=>'string',
            'price'=>'float',
            'user_picture_ids'=>'string',
            'worder_picture_ids'=>'string',
            'add_time'=>'int',
            'add_ip'=>'int',
            'update_time'=>'int',
            'update_ip'=>'int',
            'consignee'=>'string',
            'mobile'=>'string',
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
            'create_order_code',
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

    protected function _after_find(&$result,$options='') {
        parent::_after_select($result,$options);
        $this->_after_query_row($result);
        //echo '<pre>';
        //print_r($result);
    }

    /**
     * 处理point类型字段值
     * @param $resultSet
     * @param string $options
     */
    protected function _after_select(&$resultSet,$options) {
        parent::_after_select($resultSet,$options);
        foreach($resultSet as &$row){
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
            $type=$this->fields['_type'][$k];
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
    public function insert($success){

        $data=$this->data();

        $bind=[];
        $orderVals=[];
        $orderFlds=[];
        foreach($data as $key=>$val){
            if(!in_array($key,$this->fields,true)){
                unset($data[$key]);
                continue;
            }
            $bindName=":$key";
            if($this->fields['_type'][$key]=='point'){
                $orderVals[]="st_geomfromtext($bindName)";
                $bind[$bindName]="POINT($val)";
                $orderFlds[]=$key;
            }else{
                $orderVals[]=$bindName;
                $bind[$bindName]=$val;
                $orderFlds[]=$key;
            }
        }

        return do_transaction([
            ['sql'=>'INSERT INTO sq_order_vehicle('.implode(',',$orderFlds).') VALUES('.implode(',',$orderVals).');',
            'bind'=>$bind,
            'newId'=>true]
        ]);
    }

    public function update($id){

        $data=$this->data();

        //检查状态变化是否合法
        $order=$this->find($id);
        if(isset($data['status']))
            $this->_assert_new_status($order['status'],$data['status']);

        $bind=[];
        $sets=[];
        foreach($data as $key=>$val){
            if($key==$this->pk)
                continue;
            if(!in_array($key,$this->fields,true)){
                unset($data[$key]);
                continue;
            }
            $bindName=":$key";
            if($this->fields['_type'][$key]=='point'){
                $bind[$bindName]="POINT($val)";
                $sets[]=$key.'='."st_geomfromtext($bindName)";
            }else{
                $bind[$bindName]=$val;
                $sets[]=$key.'='.$bindName;
            }
        }

        //var_dump($bind);die;

        if(empty($sets))
            return false;

        $bind[':id']=$id;
        return do_transaction([
            ['sql'=>'UPDATE sq_order_vehicle set '.implode(',',$sets).' where id=:id;',
                'bind'=>$bind,
                'newId'=>false]
        ]);

    }

    /**
     * 用户取消订单
     * @author WangJiang
     * @param $oid
     * @param $uid
     */
    public function userCancel($oid,$uid){
        $data=$this->find($oid);
        //print_r($data);die;
        $this->_assert_new_status($data['status'],self::STATUS_CANCELED);
        if($data['user_id']!=$uid)
            E('非本人操作');
        $this->save(['id'=>$data['id'],'status'=>self::STATUS_CANCELED]);
    }

    private static function _get_status_chain(){
        return [
            ['id'=>0,[1,6]],
            ['id'=>1,[2,6,0]],
            ['id'=>2,[3,6]],
            ['id'=>3,[4]],
            ['id'=>4,[5]]
        ];
    }

    /**
     * @param $oldStatus
     * @param $newStatus
     */
    private function _assert_new_status($oldStatus,$newStatus){
        foreach(self::_get_status_chain() as $i){
            if($i['id']==$oldStatus){
                if(!in_array($newStatus,$i[0]))
                    E('当前状态不能改变为指定状态');
                return;
            }
        }
        E('当前状态不能在改变');
    }

    /**
     * 洗车工修改订单状态
     * @autjor WangJiang
     * @param $oid
     * @param $uid
     * @param $status
     */
    public function workerChaneStatus($oid,$uid,$status){
        $data=$this->find($oid);
        if($data['worker_id']!=$uid)
            E('非本人操作');
        $this->_assert_new_status($data['status'],$status);
        $this->save(['id'=>$data['id'],'status'=>$status]);
        //TODO 消息推送
    }

    public function getUserList($uid,$status,$payStatus,$orderCode,$page, $pageSize){
        $pageSize > 50 and $pageSize = 50;
        $where['sq_order_vehicle.user_id']=$uid;

        if(!is_null($status)){
            if(is_array($status))
                $where['sq_order_vehicle.status']=['in',$status];
            else
                $where['sq_order_vehicle.status']=$status;
        }
        if(!is_null($payStatus))
            $where['sq_order_vehicle.pay_status']=$payStatus;
        if(!is_null($orderCode))
            $where['sq_order_vehicle.order_code']=$orderCode;
        $data = $this
            ->join('left join sq_merchant_shop on sq_merchant_shop.id=sq_order_vehicle.shop_id')
            ->join('left join sq_picture on sq_picture.id=sq_merchant_shop.picture')
            //->join('left join sq_ucenter_member on sq_ucenter_member.id=sq_order_vehicle.user_id')
            ->field(['st_astext(sq_order_vehicle.lnglat) as lnglat',
                'sq_order_vehicle.id',
                'ifnull(sq_merchant_shop.title,\'\') as shop_title',
                'ifnull(sq_picture.path,\'\') as shop_picture',
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
            ])
            ->where($where)
            ->page($page, $pageSize)
            ->order('sq_order_vehicle.update_time desc')
            ->select();

        foreach($data as &$i){
            $i['worker_pictures']=[];
            foreach(D('Picture')
                        ->field(['path'])
                        ->where(['id'=>['in',$i['worder_picture_ids']]])
                        ->select() as $p){
                $i['worker_pictures'][]=$p['path'];
            }
            unset($i['worder_picture_ids']);
            $i['user_pictures']=[];
            foreach(D('Picture')
                        ->field(['path'])
                        ->where(['id'=>['in',$i['user_picture_ids']]])
                        ->select() as $p){
                $i['user_pictures'][]=$p['path'];
            }
            unset($i['user_picture_ids']);
        }

        return $data;
    }

}
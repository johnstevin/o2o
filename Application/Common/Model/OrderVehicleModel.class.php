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
        'lnglat',
        'car_number',
        'price',
        'user_picture_ids',
        'worder_picture_ids',
        'add_time',
        'add_ip',
        'update_time',
        'update_ip',
        '_type' => [
            'id'=>'int',
            'order_code'=>'string',
            'user_id'=>'int',
            'shop_id'=>'int',
            'status'=>'int',
            'worker_id'=>'int',
            'address'=>'string',
            'lnglat'=>'point',
            'car_number'=>'string',
            'price'=>'float',
            'user_picture_ids'=>'string',
            'worder_picture_ids'=>'string',
            'add_time'=>'int',
            'add_ip'=>'int',
            'update_time'=>'int',
            'update_ip'=>'int',
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
            self::MODEL_UPDATE,
            'function'
        ],
        [
            'update_ip',
            'get_client_ip',
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
            '价格格式错误'
        ],
        [
            'address',
            'require',
            '地址不能为空'
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
        'order_code'
    ];

//    protected function _after_find(&$result,$options='') {
//        parent::_after_select($result,$options);
//        $this->_after_query_row($result);
//        //echo '<pre>';
//        //print_r($result);
//    }

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
        $bind=[];
        $data=$this->data();
        $orderVals=[];
        $orderFlds=[];
        foreach($data as $key=>$val){
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
        $sql='INSERT INTO sq_order_vehicle('.implode(',',$orderFlds).') VALUES('.implode(',',$orderVals).');';
        return db_transaction($sql, $bind,$success);
    }

    public function getAvalibleWorker($data){

    }

    /**
     * 取消订单
     * @author WangJiang
     * @param $oid
     * @param $uid
     */
    public function cancel($oid,$uid){
        $data=$this->find($oid);
        //print_r($data);die;
        if($data['status']>=self::STATUS_CONFIRM)
            E('该订单已经开始处理，不能取消。');

        if($data['user_id']!=$uid)
            E('非本人操作');

        $data['status']=self::STATUS_CANCELED;

        $this->save($data);
    }
}
<?php
namespace Common\Model;

use Think\Exception;
use Think\Model\AdvModel;

/**
 * 商家模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class MerchantModel extends AdvModel
{
    protected static $model;

    protected $pk = 'id';
    protected $autoinc = false;

    const DEFAULT_PHOTO = 'Uploads/Product/2015/06/24/233.jpg';
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭

    //模型的字段
    protected $fields = [
        'id',
        'description',
        'pid',
        'login',
        'last_login_ip',
        'last_login_time',
        'status',
        'number',
        'lnglat',
        'grade_1',
        'grade_2',
        'grade_3',
        'total_orders',
        '_type' => [
            'id' => 'int',
            'description' => 'varchar',
            'pid' => 'int',
            'login' => 'int',
            'last_login_ip' => 'int',
            'last_login_time' => 'int',
            'status' => 'tinyint',
            'number' => 'string',
            'lnglat'=>'point',
            'grade_1' => 'tinyint',
            'grade_2' => 'tinyint',
            'grade_3' => 'tinyint',
            'total_orders'=>'int',
        ]
    ];
    /**
     * 只读字段
     * @var array
     */
    protected $readonlyField = ['id'];

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = [
        [
            'title',
            'require',
            '标题不能为空',
            self::MUST_VALIDATE
        ],
        [
            'brand_id',
            'number',
            '品牌ID类型非法'
        ],
        [
            'price',
            'currency',
            '价格非法',
            self::MUST_VALIDATE
        ],
        [
            'add_ip',
            'checkIpFormat',
            'IP非法',
            self::MUST_VALIDATE,
            'function'
        ],
        [
            'edit_ip',
            'checkIpFormat',
            'IP非法',
            self::MUST_VALIDATE,
            'function'
        ],
        [
            'status',
            'number',
            '状态非法',
            self::MUST_VALIDATE
        ],
        [
            'status',
            [
                self::STATUS_CLOSE,
                self::STATUS_ACTIVE
            ],
            '状态的范围不正确',
            self::MUST_VALIDATE,
            'in'
        ],


        [
            'title',
            'require',
            '商家名称不能为空',
            self::MUST_VALIDATE
        ],
        [
            'pid',
            'checkMerchantPidExist',
            '父级ID非法',
            self::MUST_VALIDATE,
            'callback'
        ],
        [
            'last_login_ip',
            'checkIpFormat',
            'IP地址格式不正确',
            self::VALUE_VALIDATE,
            'function'
        ],
        [
            'status',
            [
                self::STATUS_ACTIVE,
                self::STATUS_CLOSE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ]
    ];

    /**
     * 自动完成
     * @var array
     */
    protected $_auto = [
        [
            'status',
            self::STATUS_ACTIVE,
            self::MODEL_INSERT
        ],
        [
            'pid',
            0,
            self::MODEL_INSERT
        ]
    ];

    protected function _after_find(&$result,$options='') {
        parent::_after_select($result,$options);
        $this->_after_query_row($result);
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
     * 获得附近洗车工
     * @author WangJiang
     * @param $lat
     * @param $lng
     * @param int $range
     * @param $presetTime 预定时间，绝对时间戳，必须
     * @param $name
     * @param $number
     * @param $page
     * @param $pageSize
     */
    public function getCarWashersNearby($lat, $lng, $range,$presetTime,$name,$number,$page,$pageSize,$order){
        $pageSize > 50 and $pageSize = 50;
        $timeRange=C('AUTO_MERCHANT_SCAN.PRESET_TIME');

        /*排序*/
        if ($order === 'order') {
            $order='orders desc';
        } elseif ($order==='time') {
            $order= 'times';
        }else{
            $order='ST_Distance_Sphere(sq_merchant_shop.lnglat,POINT(:lng,:lat))';
        }


        $this->join('JOIN sq_ucenter_member on sq_ucenter_member.id=sq_merchant.id');
        $this->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo');
        $this->join('left join sq_order_vehicle on sq_order_vehicle.worker_id=sq_merchant.id');

        $bind[':presetTime']=$presetTime-$timeRange;
        $bind[':roleId']=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_WORKER');
        $where['_string']=build_distance_sql_where($lng,$lat, $range,$bind,'sq_merchant.lnglat').
            ' and ST_Distance_Sphere(sq_merchant.centre_lnglat,sq_merchant.lnglat) <= sq_merchant.service_scope
              and sq_merchant.status=1
              and sq_merchant.id in (select uid from sq_auth_access where role_id=:roleId and sq_auth_access.status=1)
              and sq_merchant.id not in (select worker_id from sq_order_vehicle
                where preset_time>:presetTime and sq_order_vehicle.status in (1,2,3) and worker_id=sq_merchant.id)';


        if(!is_null($number))
            $where['sq_merchant.number']=$number;

        if(!is_null($name))
            $where['sq_ucenter_member.real_name']=['like',"%$name%"];

        $data=$this
            ->join('left join sq_merchant_shop on sq_merchant_shop.group_id in
                (select sq_auth_access.group_id from sq_auth_access where
                        sq_auth_access.uid=sq_merchant.id
                        and sq_auth_access.status=1
                        and sq_auth_access.role_id=:roleId)')
            ->where($where)
            ->field(['sq_merchant.id'
                ,'ifnull(sq_merchant_shop.id,0) as shop_id'
                ,'ifnull(sq_merchant.number,\'\') as number'
                ,'sq_ucenter_member.mobile'
                ,'sq_ucenter_member.real_name'
                ,'sq_ucenter_member.photo'
                ,'ST_Distance_Sphere(sq_merchant.lnglat,POINT(:lng,:lat)) as distance'
                ,'st_astext(sq_merchant.lnglat) as lnglat'
                ,'sq_merchant.grade_1'
                ,'sq_merchant.grade_2'
                ,'sq_merchant.grade_3'
                ,'(sq_merchant.grade_1+sq_merchant.grade_2+sq_merchant.grade_3)/3 as grade'
                ,'ifnull(sq_picture.path,\'\') as photo_path'
                ,'sq_merchant.total_orders'
                ,'count(sq_order_vehicle.id) as orders'
                ,'(:presetTime - sq_order_vehicle.preset_time) as times'
            ])
            ->bind($bind)
            ->page($page,$pageSize)
            ->order($order)
            ->group('sq_merchant.id')
            //->fetchSql()
            ->select();
        //var_dump($data);die;

//        foreach($data as &$i){
//            $i['orders']=D('OrderVehicle')->where(['worker_id'=>$i['id']
//                ,'status'=>['in',[
//                    OrderVehicleModel::STATUS_HAS_WORKER,
//                    OrderVehicleModel::STATUS_TREATING,
//                    OrderVehicleModel::STATUS_CONFIRM]]])->count();
//        }

        return $data;
    }

    public function getAvailableWorker($lng,$lat,$presetTime){
        $range=C('AUTO_MERCHANT_SCAN.RANGE');
        $timeRange=C('AUTO_MERCHANT_SCAN.PRESET_TIME');

        $bind[':presetTime']=$presetTime-$timeRange;
        $bind[':roleId']=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_WORKER');
        $where=build_distance_sql_where($lng,$lat, $range,$bind,'sq_merchant.lnglat').
            ' and ST_Distance_Sphere(sq_merchant.centre_lnglat,sq_merchant.lnglat) <= sq_merchant.service_scope
              and sq_merchant.id in (select uid from sq_auth_access where role_id=:roleId and sq_auth_access.status=1)
              and sq_merchant.id not in (select worker_id from sq_order_vehicle
                where preset_time>:presetTime and sq_order_vehicle.status in (1,2,3) and worker_id=sq_merchant.id)';

        $data=$this
            ->join('left join sq_merchant_shop on sq_merchant_shop.group_id in
                        (select sq_auth_access.group_id from sq_auth_access where
                        sq_auth_access.uid=sq_merchant.id
                        and sq_auth_access.status=1
                        and sq_auth_access.role_id=:roleId)')
            ->where($where)->bind($bind)
            ->field(['sq_merchant.id','ifnull(sq_merchant_shop.id,0) as shop_id'])
            ->order('ST_Distance_Sphere(sq_merchant.lnglat,POINT(:lng,:lat))')
            //->fetchSql()
            ->select();

        //var_dump($data);die;

        return $data;
    }

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return MerchantModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 活取所有状态的数组
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_CLOSE => '关闭',
            self::STATUS_ACTIVE => '正常'
        ];
    }

    /**
     * 检测商家是否存在
     * @param int $id 商家ID
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return bool
     */
    public static function checkMerchantExist($id)
    {
        $id = intval($id);
        return self::getInstance()->get($id) ? true : false;
    }

    /**
     * 检测父级ID是否合法
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $pid 父级ID
     * @return bool
     */
    public static function checkMerchantPidExist($pid)
    {
        return ($pid == 0 || self::checkMerchantExist($pid)) ? true : false;
    }

    /**
     * 根据ID获取商家信息
     * @param int $id 商家ID
     * @return array|null
     */
    public function get($id,$lng=null,$lat=null)
    {
        $field=[
            'sq_merchant.id',
            'number',
            'grade_1',
            'grade_2',
            'grade_3',
            '(grade_1+grade_2+grade_3)/3 grade',
            'total_orders',
            'st_astext(lnglat) as lnglat',
            'real_name',
            'ifnull(sq_picture.path,\'\') as photo_path',];

        $bind=[];
        if(is_numeric($lng) and is_numeric($lat)){
            $field[]='ST_Distance_Sphere(lnglat,POINT(:lng,:lat)) as distance';
            $bind[':lng']=$lng;
            $bind[':lat']=$lat;
        }

        $data= $this
            ->field($field)
            ->join('join sq_ucenter_member on sq_ucenter_member.id=sq_merchant.id')
            ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
            ->bind($bind)
            ->where(['sq_merchant.id'=>$id])
            ->find();
        if(empty($data))
            E('该员工不存在');
        $data['orders']=D('OrderVehicle')->where(['worker_id'=>$data['id'],'status'=>['in',[
                OrderVehicleModel::STATUS_HAS_WORKER,
                OrderVehicleModel::STATUS_TREATING,
                OrderVehicleModel::STATUS_CONFIRM]]])->count();
        return $data;
    }

    /**
     * 获取商户信息
     * @param $mapUid
     * @param string $field
     * @return mixed|string
     */
    public function getInfos( $mapUid, $field = '*' ){
        try{
            $userInfo=$this
                ->field($field)
                ->table('__UCENTER_MEMBER__ a')
                ->join('__MERCHANT__ b ON  a.id = b.id','LEFT')
                ->join('__PICTURE__ c ON  a.photo = c.id', 'LEFT')
                ->where(array('a.is_merchant'=>array('eq', '1'),'a.id'=>$mapUid))
                ->select();

            //头像为空时返回默认图片
            foreach($userInfo as &$key) {
                if (empty($key['photo'])) {
                    $key['photo'] = self::DEFAULT_PHOTO;
                }
            }

//            if(empty($userInfo))
//                E('未找到相关资料');
            return $userInfo;

        }catch (\Exception $ex){
            return $ex->getMessage();
        }
    }

    /**
     * 带分页的获取商户信息
     * @param $mapUid
     * @param string|bool|array $field
     * @param int $pageSize 分页大小
     * @return mixed|string
     */
    public function getStaffInfos( $mapUid, $field = true,$pageSize = 20 ){
        try{

            $p=I('p');
            if(empty($p))
                E('请传入页码参数');
            $userInfo=$this
                ->field($field)
                ->table('__UCENTER_MEMBER__ a')
                ->join('__MERCHANT__ b ON  a.id = b.id','LEFT')
                ->join('__PICTURE__ c ON  a.photo = c.id', 'LEFT')
                ->where(array('a.is_merchant'=>array('eq', '1'),'a.id'=>$mapUid))
                ->page($p,$pageSize)
                ->select();

            //头像为空时返回默认图片
            foreach($userInfo as &$key) {
                if (empty($key['photo'])) {
                    $key['photo'] = self::DEFAULT_PHOTO;
                }
            }

//            if(empty($userInfo))
//                E('未找到相关资料');
            return $userInfo;

        }catch (\Exception $ex){
            return $ex->getMessage();
        }
    }


    public function saveInfo($data)
    {
        try {

            if(!is_array($data)) {
                E('参数错误');
            }

         empty($data) ? E('修改字段不能为空'):'';


            $uid=$data['uid'];
            $service_scope=intval($data['service_scope']);
            $centre_lnglat=$data['centre_lnglat'];

            $arr = explode(",",$centre_lnglat);
            if ($arr[0]== 0||$arr[1]==0)

                E('定位失败,请重新定位！');

            $centre_ads=$data['centre_ads'];
            if (!is_numeric($service_scope))
                E('范围必须是数值');

            $this->execute("update __MERCHANT__ set centre_lnglat=point($centre_lnglat),service_scope=$service_scope,centre_ads='$centre_ads' where id=$uid");

            return true;

        } catch (\Exception $ex) {

            return $ex->getMessage();

        }
    }



    /**
     * 保存坐标
     * @return bool|string
     */
    public function savelnglat($id,$lnglat)
    {
        return  $this->execute("update __MERCHANT__ set lnglat=point($lnglat) where id=$id");
    }
}

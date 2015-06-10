<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-5-25
 * Time: 上午11:22
 */

namespace Common\Model;

use Think\Model\AdvModel;

/*
 * @author  WangJiang
 */
class MerchantShopModel extends AdvModel{
    ## 状态常量
    const STATUS_DELETE=-1;//软删除
    const STATUS_CLOSE = 0;//待审核,关闭
    const STATUS_ACTIVE = 1;//审核通过,正常
    const STATUS_CHECKING = 2;//审核中
    const STATUS_DENIED = 3;//审核未通过

    /**
     * @author  WangJiang
     * @var array
     */
    protected $fields=[
        'id',
        'title',
        'description',
        'group_id',
        'status',
        'lnglat',
        'type',
        'open_status',
        'open_time_mode',
        'begin_open_time',
        'end_open_time',
        'delivery_range',
        'phone_number',
        'address',
        'pid',
        'add_uid',
        'region_id',
        '_type'=>[
            'id'=>'int',
            'title'=>'string',
            'description'=>'string',
            'group_id'=>'int',
            'status'=>'int',
            'lnglat'=>'point',
            'type'=>'int',
            'open_status'=>'int',
            'open_time_mode'=>'int',
            'begin_open_time'=>'int',
            'end_open_time'=>'int',
            'delivery_range'=>'int',
            'phone_number'=>'string',
            'address'=>'string',
            'pid'=>'int',
            'add_uid'=>'int',
            'region_id'=>'int',
        ]
    ];

    /**
     * @author  WangJiang
     * @var string
     */
    protected $pk     = 'id';

    /**
     * @author  WangJiang
     * @var array
     */
    protected $_auto = array (
        [##禁止客户端修改该值
            'group_id',
            'default_group_id',
            self::MODEL_BOTH,
            'callback'
        ],
        ['status',self::STATUS_CLOSE,self::MODEL_INSERT]
    );

    public function default_group_id(){
        return C('AUTH_GROUP_ID.MERCHANT_GROUP_ID');
    }

    protected $readonlyField=[
        'type',
        'pid',
        'add_uid',
        'region_id',
    ];

    /**
     * 验证规则
     * @author WangJiang
     * @var array
     */
//    protected $_validate = [
//        [
//            'type',
//            'is_null',
//            '不允许修改type',
//            self::MUST_VALIDATE,
//            'function',
//            self::MODEL_UPDATE
//        ],
//        [
//            'pid',
//            'is_null',
//            '不允许修改pid',
//            self::MUST_VALIDATE,
//            'function',
//            self::MODEL_UPDATE
//        ],
//        [
//            'add_uid',
//            'is_null',
//            '不允许修改add_uid',
//            self::MUST_VALIDATE,
//            'function',
//            self::MODEL_UPDATE
//        ],
//        [
//            'region_id',
//            'is_null',
//            '不允许修改region_id',
//            self::MUST_VALIDATE,
//            'function',
//            self::MODEL_UPDATE
//        ],
//    ];

    /**
     * 查询周边商铺
     * @author  WangJiang
     * @param double $lat 查询中心维度，必须是百度坐标
     * @param double $lng 查询中心经度，必须是百度坐标
     * @param int $range 查询半径，单位米，缺省100米
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param int $type 商家门店类型，可选0-所有类型，1-超市，2-生鲜，3-洗车，4-送水，缺省0
     * @return mixed
     */
    public function getNearby($lat, $lng, $range = 100,$words=null,$words_op='or',$type=0)
    {
        if (!is_numeric($lat) or !is_numeric($lng))
            //$this->error('坐标必须是数值', '', true);
            E('坐标必须是数值');

        if ($lat < -90 or $lat > 90 or $lng < -180 or $lng > 180)
            //$this->error('非法坐标', '', true);
            E('非法坐标');

        if (!is_numeric($range))
            //$this->error('查询范围必须是数值', '', true);
            E('查询范围必须是数值');

        //TODO：需要考虑最大查询范围
        if ($range < 0)
            //$this->error('非法查询范围', '', true);
            E('非法查询范围');

        $range=floatval($range);
        $range+=$range*0.15;

        //当前时间，秒
        $seconds=time()-strtotime("00:00:00");//8*3600;

        $map['_string'] = 'ST_Distance_Sphere(lnglat,POINT(:lng,:lat))<:dist and (open_time_mode=2 or (begin_open_time <:seconds and end_open_time >:seconds))';

        $type=intval($type);

        if (!in_array($type, C('SHOP_TYPE')))
            //$this->error('非法店面类型，可选项：0-所有类型，1-超市，2-生鲜，3-洗车，4-送水', '', true);
            E('非法店面类型，可选项：0-所有类型，17 => 超市, 89 => 生鲜, 18 => 洗车, 90 => 送水');

        if ($type != 0)
            $map['type'] = $type;

        if (!empty($words))
            build_words_query(explode(',', $words), $words_op, ['title', 'description'], $map);

        $map['status&open_status']=1;

        $this->where($map)
            ->bind(':lng', $lng)
            ->bind(':lat', $lat)
            ->bind(':dist', $range)
            ->bind(':seconds',$seconds)
            ->field(['id','title','description','type','phone_number','address','open_time_mode','begin_open_time',
                'end_open_time','pay_delivery_time','delivery_time_cost','delivery_distance_limit','pay_delivery_distance',
                'delivery_distance_cost','free_delivery_amount','pay_delivery_amount','delivery_amount_cost'
                ,'ST_Distance_Sphere(lnglat,POINT(:lng,:lat)) as distance','st_astext(lnglat) as lnglat']);

        $ret= $this->select();
        //print_r($this->getLastSql());
        return $ret;
    }

    /**
     * 根据ID获取商铺信息
     * @author  WangJiang
     * @param int $id 商铺ID
     * @return array|null
     */
    public function get($id)
    {
        $id = intval($id);
        if (!$id) return null;
        return $this->field(['id', 'title', 'description', 'type', 'open_status', 'open_time_mode'
            , 'begin_open_time', 'end_open_time', 'delivery_range', 'phone_number', 'address', 'st_astext(lnglat) as lnglat'])->find($id);
    }

    /**
     * 增加一条商铺信息
     * @author  WangJiang
     * @throws Exception
     * @throws \Exception
     */
    public function add(){

        $bind=[];
        $data=$this->data();
        $vals=[];
        foreach($data as $key=>$val){
            $bindName=":$key";
            if($this->fields['_type'][$key]=='point'){
                $vals[]="st_geomfromtext($bindName)";
                $bind[$bindName]="POINT($val)";
            }else{
                $vals[]=$bindName;
                $bind[$bindName]=$val;
            }
        }

        $sql='INSERT INTO sq_merchant_shop('.implode(',',array_keys($data)).') VALUES('.implode(',',$vals).');';

        //print_r($sql);die;
        return $this->doTransaction($sql, $bind);
    }

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
     * 修改一条商铺信息
     * @author  WangJiang
     * @throws Exception
     * @throws \Exception
     */
    public function save(){

        $bind=[];
        $data=$this->data();
        $set=[];
        $where=null;
        foreach($data as $key=>$val){
            $bindName=":$key";
            if($key==$this->pk) {
                $where = "$key=$bindName";
                $bind[$bindName] = $val;
            }else{
                if($this->fields['_type'][$key]=='point'){
                    $set[]="$key=st_geomfromtext($bindName)";
                    $bind[$bindName]="POINT($val)";
                }else{
                    $set[]="$key=$bindName";
                    $bind[$bindName]=$val;
                }
            }
        }

        $sql='UPDATE sq_merchant_shop set '.implode(',',$set)." WHERE $where;";
        //print_r($sql);die;

        $this->doTransaction($sql, $bind);
    }

    /**
     * 在实务环境下执行一条语句
     * @author  WangJiang
     * @param $sql  SQL语句
     * @param $bind 绑定参数
     * @throws Exception
     * @throws \Exception
     */
    public function doTransaction($sql, $bind)
    {
        //TODO:目前ThinkPHP不支持空间类型字段
        $dbh = new \PDO(C('DB_TYPE') . ':host=' . C('DB_HOST') . ';dbname=' . C('DB_NAME') . ';port=' . C('DB_PORT'), C('DB_USER'), C('DB_PWD'));
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $stmt = $dbh->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $newid=null;
        try {
            $dbh->beginTransaction();
            $stmt->execute();
            $newid= $dbh->lastInsertId();
            //test for transaction
            //throw new Exception();
            $dbh->commit();
            return $newid;
        } catch (Exception $e) {
            $dbh->rollBack();
            throw $e;
        } finally {
            unset($dbh);
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
            }else if($type=='int'){
                $v=intval($v);
            }else if($type=='float'){
                $v=floatval($v);
            }
            if($k=='distance'){
                $v=floatval($v);
            }
        }
    }

}
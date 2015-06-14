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
        'phone_number',
        'address',
        'pid',
        'add_uid',
        'region_id',
        'picture',
        'staff_register_url',
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
            'phone_number'=>'string',
            'address'=>'string',
            'pid'=>'int',
            'add_uid'=>'int',
            'region_id'=>'int',
            'picture'=>'int',
            'staff_register_url'=>'string'
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
        ['type',1,self::MODEL_INSERT],
        ['status',self::STATUS_CLOSE,self::MODEL_INSERT]
    );

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
     * @param int $tagId 商家门店服务类型，可选''表示所有店铺，'商超'，'生鲜'，'洗车'，'送水'，缺省为''
     * @param int $type 商家类别，有超市，洗车
     * @param int $order 排序，1-按距离，2-按评价
     * @return mixed
     */
    public function getNearby($lat, $lng, $range,$words,$words_op,$tagId=0,$type=null,$order=1)
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

        //TODO 需要考虑最大查询范围
        if ($range < 0)
            //$this->error('非法查询范围', '', true);
            E('非法查询范围');

        $range=floatval($range);
        $range+=$range*0.15;

        //当前时间，秒
        $seconds=time()-strtotime("00:00:00");//8*3600;

        $map['_string'] = 'ST_Distance_Sphere(sq_merchant_shop.lnglat,POINT(:lng,:lat))<:dist
        and (sq_merchant_shop.open_time_mode=2
            or (sq_merchant_shop.begin_open_time <:seconds and sq_merchant_shop.end_open_time >:seconds))';

        if(!is_null($type))
            $map['type']=$type;

//        if (!in_array($tag, C('SHOP_TAG')))
//            E('非法店面服务，可选项：\'\'表示所有店铺，\'商超\'，\'生鲜\'，\'洗车\'，\'送水\'');

        if ($tagId!=0){
            $this->join('inner join sq_shop_tag on shop_id=sq_merchant_shop.id and tag_id=:tag_id');
            $this->bind(':tag_id',$tagId);
        }

        if (!empty($words))
            build_words_query(explode(',', $words), $words_op, ['sq_merchant_shop.title', 'sq_merchant_shop.description'], $map);

        $map['sq_merchant_shop.status&sq_merchant_shop.open_status']=1;

        $this->where($map)
            ->join('LEFT JOIN sq_appraise on sq_appraise.shop_id = sq_merchant_shop.id')
            ->bind(':lng', $lng)
            ->bind(':lat', $lat)
            ->bind(':dist', $range)
            ->bind(':seconds',$seconds)
            ->field([
                'sq_merchant_shop.id'
                ,'sq_merchant_shop.title'
                ,'sq_merchant_shop.picture'
                ,'sq_merchant_shop.description'
                ,'sq_merchant_shop.type'
                ,'sq_merchant_shop.phone_number'
                ,'sq_merchant_shop.address'
                ,'sq_merchant_shop.open_time_mode'
                ,'sq_merchant_shop.begin_open_time'
                ,'sq_merchant_shop.end_open_time'
                ,'sq_merchant_shop.pay_delivery_time'
                ,'sq_merchant_shop.delivery_time_cost'
                ,'sq_merchant_shop.delivery_distance_limit'
                ,'sq_merchant_shop.pay_delivery_distance'
                ,'sq_merchant_shop.delivery_distance_cost'
                ,'sq_merchant_shop.free_delivery_amount'
                ,'sq_merchant_shop.pay_delivery_amount'
                ,'sq_merchant_shop.delivery_amount_cost'
                ,'ST_Distance_Sphere(sq_merchant_shop.lnglat,POINT(:lng,:lat)) as distance'
                ,'st_astext(sq_merchant_shop.lnglat) as lnglat'
                ,'sq_appraise.grade_1'
                ,'sq_appraise.grade_2'
                ,'sq_appraise.grade_3'
                ,'(avg(sq_appraise.grade_1)+avg(sq_appraise.grade_2)+avg(sq_appraise.grade_3))/3 as grade']);

        if(1==$order)
            $this->order('distance');
        //TODO 暂时即时计算，后期应该定期计算存入shop表
        else if(2==$order)
            $this->order('grade desc');

        $this->group('sq_merchant_shop.id');

        $ret= $this->select();
        //print_r($this->getLastSql());die;
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
        $sql.='UPDATE sq_auth_access SET role_id=:role_id WHERE uid=:uid;';
        $bind[':uid']=$data['add_uid'];
        $bind[':role_id']=$data['type']==1
            ?C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER')
            :C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER');
        //var_dump($bind);die;

        //print_r($sql);die;
        return $this->doTransaction($sql, $bind);
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
        $id=$data['id'];
        $set=[];
        $where=null;
        foreach($data as $key=>$val){
            $bindName=":$key";
            if($key!=$this->pk) {
                if($this->fields['_type'][$key]=='point'){
                    $set[]="$key=st_geomfromtext($bindName)";
                    $bind[$bindName]="POINT($val)";
                }else{
                    $set[]="$key=$bindName";
                    $bind[$bindName]=$val;
                }
            }
        }

        $where=' id=:id';
        $bind[':id']=$id;

        //var_dump($data);die;

        $sql='UPDATE sq_merchant_shop set '.implode(',',$set)." WHERE $where;";

        $this->doTransaction($sql, $bind);
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
            if($k=='picture_ids'){
                $v=explode(',',$v);
            }if($k=='grade'){
                $v=intval($v);
            }
        }
    }

}
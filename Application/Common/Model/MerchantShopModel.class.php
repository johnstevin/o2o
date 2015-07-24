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

class MerchantShopModel extends AdvModel
{
    public static $model;//当前模型实例
    ## 状态常量
    const STATUS_DELETE=-1;//软删除
    const STATUS_CLOSE = 2;//待审核,关闭
    const STATUS_ACTIVE = 1;//审核通过,正常
    const STATUS_DENIED = 3;//审核未通过

    ## 开放状态
    const OPEN_STATUS_OPEN = 1;//营业
    const OPEN_STATUS_CLOSE = 0;//歇业

    const TYPE_MALL=1;
    const TYPE_CAR_WASH=2;

    /**
     * @author  WangJiang
     * @var array
     */
    protected $fields = [
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
        'add_uid',
        'region_id',
        'picture',
        'staff_register_url',
        'tags',
        'pay_delivery_time_begin',
        'pay_delivery_time_end',
        'delivery_time_cost',
        'delivery_distance_limit',
        'pay_delivery_distance',
        'delivery_distance_cost',
        'free_delivery_amount',
        'pay_delivery_amount',
        'delivery_amount_cost',
        'yyzz_picture',
        'spwsxkz_picture',
        'id_cart_front_picture',
        'id_cart_back_picture',
        'pay_delivery_time_begin',
        'delivery_time_cost',
        'delivery_distance_limit',
        'pay_delivery_distance',
        'delivery_distance_cost',
        'free_delivery_amount',
        'pay_delivery_amount',
        'delivery_amount_cost',
        'grade_1',
        'grade_2',
        'grade_3',
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
            'add_uid'=>'int',
            'region_id'=>'int',
            'picture'=>'int',
            'staff_register_url'=>'string',
            'tags'=>'array',
            'yyzz_picture'=>'int',
            'spwsxkz_picture'=>'int',
            'id_cart_front_picture'=>'int',
            'id_cart_back_picture'=>'int',
            'pay_delivery_time_begin'=>'int',
            'pay_delivery_time_end'=>'int',
            'delivery_time_cost'=>'int',
            'delivery_distance_limit'=>'int',
            'pay_delivery_distance'=>'int',
            'delivery_distance_cost'=>'int',
            'free_delivery_amount'=>'int',
            'pay_delivery_amount'=>'int',
            'delivery_amount_cost'=>'int',
            'grade_1' => 'tinyint',
            'grade_2' => 'tinyint',
            'grade_3' => 'tinyint',
        ]
    ];

    /**
     * @author  WangJiang
     * @var string
     */
    protected $pk = 'id';

    /**
     * @author  WangJiang
     * @var array
     */
    protected $_auto = [
        //TODO liuhui type默认被定死了
        //['type',1,self::MODEL_INSERT],
        ['status',self::STATUS_CLOSE,self::MODEL_INSERT],
        [
            'create_time',
            'time',
            self::MODEL_INSERT,
            'function'
        ],
        [
            'edit_time',
            'time',
            self::MODEL_BOTH,
            'function'
        ],
    ];

    protected $autoinc=true;

    protected $readonlyField=[
        'add_uid',
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
//        [
//            'address',
//            'is_null',
//            '不允许修改region_id',
//            self::MUST_VALIDATE,
//            'function',
//            self::MODEL_UPDATE
//        ],
//    ];

    /**
     * 获得当前模型实例
     * @author Fufeng NIe <niefufeng@gmail.com>
     * @return MerchantShopModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

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
     * @param string|null $regionName 区域名称
     * @return mixed
     */
    public function getNearby($lat, $lng, $range, $words = null, $words_op = null, $tagId = 0, $type = null, $order = 1, $regionName = null)
    {
        //,page,pageSize该函数不能采用分页，原因时客户端需要一下获得所有商铺
        if (!is_numeric($lat) or !is_numeric($lng)) E('坐标必须是数值');

        if ($lat < -90 or $lat > 90 or $lng < -180 or $lng > 180) E('非法坐标');

        if (!is_numeric($range)) E('查询范围必须是数值');

        //TODO 需要考虑最大查询范围
        if ($range < 0) E('非法查询范围');

        $range = floatval($range);
        $range += $range * 0.15;

        if ($regionName !== null) {
            $regionName = trim($regionName);
            $regionId = RegionModel::getInstance()->where(['name' => $regionName, 'status' => RegionModel::STATUS_ACTIVE])->find();
            if (!$regionId) E('没有找到相应的区域');
            $regionIds = [
                $regionId['id']
            ];
            $regions = RegionModel::getInstance()->field(['id'])->where(['pid' => $regionId['id'], 'status' => RegionModel::STATUS_ACTIVE])->select();
            $ids = array_column($regions, 'id');
            $regionIds = array_merge($regionIds, $ids);
            while ($ids) {
                if (!$regions = RegionModel::getInstance()->field(['id'])->where(['pid' => ['IN', $ids], 'status' => RegionModel::STATUS_ACTIVE])->select()) {
                    break;
                }
                $ids = array_column($regions, 'id');
                $regionIds = array_merge($regionIds, $ids);
            }
        }

        //当前时间，秒
        $seconds = time() - strtotime("00:00:00");//8*3600;

        $bind = [':seconds' => $seconds];

        //诺，注释这行呢，是王哥写的，要求不得了
//        $where = build_distance_sql_where($lng, $lat, $range, $bind, 'sq_merchant_shop.lnglat') . ' and (sq_merchant_shop.open_time_mode=2
//            or (sq_merchant_shop.begin_open_time <:seconds and sq_merchant_shop.end_open_time >:seconds))';

        $where = 'ST_Distance_Sphere(sq_merchant_shop.lnglat,point(:lng,:lat)) <= if(delivery_distance_limit >= 500, delivery_distance_limit + 111, delivery_distance_limit + 50) AND (sq_merchant_shop.open_time_mode=2 or (sq_merchant_shop.begin_open_time <:seconds and sq_merchant_shop.end_open_time >:seconds))';
        if($regionName !== null){
            $where .= ' AND sq_merchant_shop.region_id IN (' . implode(',', $regionIds) . ')';
        }
        $bind[':lng'] = floatval($lng);
        $bind[':lat'] = floatval($lat);
        if (!is_null($type)) $map['type'] = $type;

//        if (!in_array($tag, C('SHOP_TAG')))
//            E('非法店面服务，可选项：\'\'表示所有店铺，\'商超\'，\'生鲜\'，\'洗车\'，\'送水\'');

        if ($tagId != 0) {
            $where .= ' and sq_merchant_shop.id in (select shop_id from sq_shop_tag where tag_id=:tag_id)';
            //$this->join('inner join sq_shop_tag on shop_id=sq_merchant_shop.id and tag_id=:tag_id');
            $bind[':tag_id'] = $tagId;
        }

        if (!empty($words))
            build_words_query(explode(',', $words), $words_op, ['sq_merchant_shop.title', 'sq_merchant_shop.description'], $map);

        $map['_string'] = $where;
        $map['sq_merchant_shop.status&sq_merchant_shop.open_status'] = 1;
        $this->where($map)
            //->join('LEFT JOIN sq_appraise on sq_appraise.shop_id = sq_merchant_shop.id')
            ->join('LEFT JOIN sq_picture on sq_picture.id = sq_merchant_shop.picture')
            ->bind($bind)
            ->field([
                'sq_merchant_shop.id'
                , 'sq_merchant_shop.open_status'
                , 'sq_merchant_shop.status'
                , 'sq_merchant_shop.title'
                , 'sq_merchant_shop.picture'
                , 'sq_merchant_shop.description'
                , 'sq_merchant_shop.type'
                , 'sq_merchant_shop.phone_number'
                , 'sq_merchant_shop.address'
                , 'sq_merchant_shop.open_time_mode'
                , 'sq_merchant_shop.begin_open_time'
                , 'sq_merchant_shop.end_open_time'
                , 'sq_merchant_shop.pay_delivery_time_begin'
                , 'sq_merchant_shop.pay_delivery_time_end'
                , 'sq_merchant_shop.delivery_time_cost'
                , 'sq_merchant_shop.delivery_distance_limit'
                , 'sq_merchant_shop.pay_delivery_distance'
                , 'sq_merchant_shop.delivery_distance_cost'
                , 'sq_merchant_shop.free_delivery_amount'
                , 'sq_merchant_shop.pay_delivery_amount'
                , 'sq_merchant_shop.delivery_amount_cost'
                , 'ST_Distance_Sphere(sq_merchant_shop.lnglat,POINT(:lng,:lat)) as distance'
                , 'st_astext(sq_merchant_shop.lnglat) as lnglat'
                , 'sq_merchant_shop.grade_1'
                , 'sq_merchant_shop.grade_2'
                , 'sq_merchant_shop.grade_3'
                , '(sq_merchant_shop.grade_1+sq_merchant_shop.grade_2+sq_merchant_shop.grade_3)/3 as grade'
                , 'ifnull(sq_picture.path,\'\') as picture_path'
            ]);

        if (1 == $order) {
            $this->order('distance');
            //TODO 暂时即时计算，后期应该定期计算存入shop表
        } else if (2 == $order) {
            $this->order('grade desc');
        }
        return $this->group('sq_merchant_shop.id')->select();
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
        return $this
            //->join('LEFT JOIN sq_appraise on sq_appraise.shop_id = sq_merchant_shop.id')
            ->join('LEFT JOIN sq_picture on sq_picture.id = sq_merchant_shop.picture')
            ->field([
            'sq_merchant_shop.id'
            ,'sq_merchant_shop.title'
            ,'sq_merchant_shop.picture'
            ,'sq_merchant_shop.description'
            ,'sq_merchant_shop.group_id'
            ,'sq_merchant_shop.type'
            ,'sq_merchant_shop.phone_number'
            ,'sq_merchant_shop.address'
            ,'sq_merchant_shop.open_time_mode'
            ,'sq_merchant_shop.begin_open_time'
            ,'sq_merchant_shop.end_open_time'
            ,'sq_merchant_shop.pay_delivery_time_begin'
            ,'sq_merchant_shop.pay_delivery_time_end'
            ,'sq_merchant_shop.delivery_time_cost'
            ,'sq_merchant_shop.delivery_distance_limit'
            ,'sq_merchant_shop.pay_delivery_distance'
            ,'sq_merchant_shop.delivery_distance_cost'
            ,'sq_merchant_shop.free_delivery_amount'
            ,'sq_merchant_shop.pay_delivery_amount'
            ,'sq_merchant_shop.delivery_amount_cost'
            ,'st_astext(sq_merchant_shop.lnglat) as lnglat'
            ,'sq_merchant_shop.grade_1'
            ,'sq_merchant_shop.grade_2'
            ,'sq_merchant_shop.grade_3'
            ,'(sq_merchant_shop.grade_1+sq_merchant_shop.grade_2+sq_merchant_shop.grade_3)/3 as grade'
            ,'ifnull(sq_picture.path,\'\') as picture_path'])
            ->where(['sq_merchant_shop.id'=>$id])
            ->find();
    }

    /**
     * 增加一条商铺信息
     * @author  WangJiang
     * @throws Exception
     * @throws \Exception
     */
    public function add(){

//        $defaults=[
//            'free_delivery_amount'=>20,
//            'pay_delivery_amount'=>100
//        ];

        $stat=[];

        $bind=[];
        $data=$this->data();
//        foreach($defaults as $key=>$val){
//            if(!array_key_exists($key,$data)){
//                $data[$key]=$val;
//            }
//        }

        $shopVals=[];
        $shopFlds=[];
        foreach($data as $key=>$val){
            if(!in_array($key,$this->fields,true)){
                unset($data[$key]);
                continue;
            }
            $bindName=":$key";
            if($this->fields['_type'][$key]=='point'){
                $shopVals[]="st_geomfromtext($bindName)";
                $bind[$bindName]="POINT($val)";
                $shopFlds[]=$key;
            }else if($key!='tags'){
                $shopVals[]=$bindName;
                $bind[$bindName]=$val;
                $shopFlds[]=$key;
            }
        }

        $stat[]=[
            'sql'=>'INSERT INTO sq_merchant_shop('.implode(',',$shopFlds).') VALUES('.implode(',',$shopVals).');',
            'bind'=>$bind,
            'newId'=>true
        ];
        $stat[]=['sql'=>'SET @sid = last_insert_id();','bind'=>[]];

        foreach($data as $key=>$val){
            if($key=='tags'){
                $a=explode(',',$val);
                $bind=[];
                $tagVals=[];
                foreach($a as $i=>$tag){
                    $bindName=":$key$i";
                    $bind[$bindName]=$tag;
                    $tagVals[]="(@sid,$bindName)";
                }
                $stat[]=[
                    'sql'=>'INSERT INTO sq_shop_tag(shop_id, tag_id) VALUES '.implode(',',$tagVals).';',
                    'bind'=>$bind
                ];
                break;
            }
        }

        //

//        $stat[]=[
//            'sql'=>'UPDATE sq_auth_access SET role_id=:role_id WHERE uid=:uid;',
//            'bind'=>[
//                ':uid'=>$data['add_uid'],
//                ':role_id'=>$data['type']==1
//                    ?C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER')
//                    :C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER')
//            ]
//        ];

        //print_r($sql);var_dump($bind);die;
        return do_transaction($stat);
    }

    /**
     * 修改一条商铺信息
     * @author  WangJiang
     * @throws Exception
     * @throws \Exception
     */
    public function save(){
        $data=$this->data();
        //var_dump($data);die;
        $this->_before_update($data);
        $id=$data['id'];

        $md=$this
            //->fetchSql()
            ->find($id);

        //var_dump($md);die;

        //只有审核中和为通过的店铺才能修改下列字段
        if(!in_array($md['status'],[self::STATUS_CLOSE,self::STATUS_DENIED])){
            //var_dump($data);
            //var_dump($md);
            unset($data['region_id']);
            unset($data['type']);
            unset($data['address']);
            unset($data['title']);
        }else
            $data['status']=self::STATUS_CLOSE;

        $set=[];
        $where=null;
        foreach($data as $key=>$val){
            if(!in_array($key,$this->fields,true)){
                unset($data[$key]);
                continue;
            }
            $bindName=":$key";
            if($key!=$this->pk) {
                if($this->fields['_type'][$key]=='point'){
                    $set[]="$key=st_geomfromtext($bindName)";
                    $bind[$bindName]="POINT($val)";
                }else if($key!='tags'){
                    $set[]="$key=$bindName";
                    $bind[$bindName]=$val;
                }
            }
        }

        $where=' id=:id';
        $bind[':id']=$id;

        $stat=[];
        if(!empty($set)){
            $stat[]=[
                'sql'=>'UPDATE sq_merchant_shop set '.implode(',',$set)." WHERE $where;",
                'bind'=>$bind
            ];
        }

        foreach($data as $key=>$val){
            if($key=='tags'){
                $stat[]=['sql'=>'DELETE FROM sq_shop_tag WHERE shop_id=:id;','bind'=>[':id'=>$id]];

                $a=explode(',',$val);
                $bind=[':sid'=>$id];
                $tagVals=[];
                foreach($a as $i=>$tag){
                    $bindName=":$key$i";
                    $bind[$bindName]=$tag;
                    $tagVals[]="(:sid,$bindName)";
                }
                $stat[]=[
                    'sql'=>'INSERT INTO sq_shop_tag(shop_id, tag_id) VALUES '.implode(',',$tagVals).';',
                    'bind'=>$bind
                ];
                break;
            }
        }

        //var_dump($stat);die;
        do_transaction($stat);
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
            } else if ($type == 'int') {
                $v = intval($v);
            } else if ($type == 'float') {
                $v = floatval($v);
            }
            if ($k == 'distance') {
                $v = floatval($v);
            }
            if($k=='picture_ids'){
                $v=explode(',',$v);
            }if($k=='grade'){
                $v=intval($v);
            }
        }
    }

    public function getProductList($shopIds = null, $categoryId = null, $title = null
        , $priceMin = null, $priceMax = null){

        list($shopBindNames, $bindValues) = build_sql_bind($shopIds);

        $where = 'sq_merchant_shop.id in (' . implode(',', $shopBindNames) . ')';

        $product_sql = 'JOIN sq_product on sq_product.id = sq_merchant_depot.product_id and sq_product.status=1';
        if (!empty($categoryId)) {
            $product_sql .= ' and sq_product.id in (select product_id from sq_product_category where category_id=:categoryId)';
            $bindValues[':categoryId'] = $categoryId;
        }
        if (!empty($title)) {
            $product_sql .= ' and sq_product.title like :title';
            $bindValues[':title'] = '%' . $title . '%';
        }

        $this->join('JOIN sq_merchant_depot on sq_merchant_shop.id=sq_merchant_depot.shop_id and sq_merchant_depot.status=1');
        $this->join($product_sql);
        $this->join('JOIN sq_brand on sq_brand.id=sq_product.brand_id');
        $this->join('JOIN sq_norms on sq_norms.id=sq_product.norms_id');
        $this->join('LEFT JOIN sq_picture as product_picture on product_picture.id = sq_product.picture');
        //$this->join('LEFT JOIN sq_picture as shop_picture on shop_picture.id = sq_merchant_shop.picture');
        $this->where($where);
        $this->bind($bindValues);

        $this->field(['sq_merchant_shop.id as shop_id'
            ,'sq_merchant_shop.title as shop_title'
            ,'sq_merchant_depot.id as depot_id'
            ,'sq_merchant_depot.price'
            ,'sq_merchant_depot.product_id'
            , 'sq_product.title as product'
            , 'sq_brand.id as brand_id'
            , 'sq_brand.title as brand'
            , 'sq_norms.id as norm_id'
            , 'sq_norms.title as norm'
            ,'ifnull(product_picture.path,\'\') as product_picture_path'
            //,'ifnull(shop_picture.path,\'\') as shop_picture_path'
        ])
            ->order('sq_merchant_depot.price');
        $data = $this->select();

        //print_r($this->getLastSql());die;

        $shop=[];
        foreach($data as $i){
            if(!array_key_exists($i['shop_id'],$shop)){
                $shop[$i['shop_id']]=[
                    'id'=>$i['shop_id'],
                    'title'=>$i['shop_title'],
                    'depots'=>[]
                ];
            }
            $shop[$i['shop_id']]['depots'][]=[
                'id'=>$i['depot_id'],
                'price'=>$i['price'],
                'price'=>$i['price'],
                'product_id'=>$i['product_id'],
                'product'=>$i['product'],
                'norm_id'=>$i['norm_id'],
                'norm'=>$i['norm'],
                'brand_id'=>$i['brand_id'],
                'brand'=>$i['brand'],
                'shop_id'=>$i['shop_id'],
                'shop'=>$i['shop_title'],
                'picture_path'=>$i['product_picture_path'],
            ];
        }
        return array_values($shop);
    }

}


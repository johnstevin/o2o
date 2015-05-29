<?php
namespace Common\Model;

use Think\Model\RelationModel;
use Think\Page;

/**
 * 商家仓库模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class MerchantDepotModel extends RelationModel
{
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭

    protected $fields = [
        'group_id',
        'product_id',
        'status',
        'price',
        'add_time',
        'add_ip',
        'remark',
        'update_time',
        'update_ip',
        '_type' => [
            'group_id' => 'int',
            'product_id' => 'int',
            'price' => 'double',
            'add_time' => 'int',
            'add_ip' => 'bigint',
            'remark' => 'varchar',
            'update_time' => 'int',
            'update_ip' => 'bigint'
        ]
    ];
    /**
     * 只读字段
     * @var array
     */
    protected $readonlyField = ['group_id', 'product_id', 'add_time', 'add_ip'];

    protected $_validate = [
        [
            'group_id',
            'require',
            '商家ID不能为空',
            self::MUST_VALIDATE
        ],
        [
            'product_id',
            'require',
            '商品ID不能为空',
            self::MUST_VALIDATE
        ],
        [
            'price',
            'require',
            '分类不能为空',
            self::MUST_VALIDATE
        ],
        [
            'group_id',
            'check_merchant_exist',
            '有非法商家ID',
            self::MUST_VALIDATE,
            'function'
        ],
        [
            'product_id',
            'check_product_exist',
            '有非法商品ID',
            self::MUST_VALIDATE,
            'function'
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
        ]
    ];

    protected $_auto = [
        [
            'add_time',
            'time',
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
            'add_ip',
            'get_client_ip',
            self::MODEL_INSERT,
            'function'
        ],
        [
            'update_ip',
            'get_client_ip',
            self::MODEL_UPDATE,
            'function'
        ],
        [
            'status',
            self::STATUS_ACTIVE,
            self::MODEL_INSERT
        ]
    ];

    /**
     * 获得当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return MerchantDepotModel
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
     * 根据商家ID查找商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $merchantId 商家ID
     * @param int $pageSize 页面大小
     * @param int $status 商品状态
     * @return array
     */
    public static function getLists($merchantId, $pageSize = 10, $status = self::STATUS_ACTIVE)
    {
        $where['id'] = intval($merchantId);
        if (!$where['id'] || check_merchant_exist($where['id'])) return ['data' => [], 'pagination' => ''];
        if (!empty($status)) $where['status'] = in_array($status, array_keys(self::getStatusOptions())) ? $status : self::STATUS_ACTIVE;
        $pageSize = intval($pageSize);
        $model = self::getInstance();
        $total = $model->where($where)->count('id');
        $pagination = new Page($total, $pageSize);
        $data = $model->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        return [
            'data' => $data,
            'pagination' => $pagination->show()
        ];
    }

    /**
     * 根据ID获取商家信息
     * @param int $id
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $fields = '*')
    {
        $id = intval($id);
        return $id ? self::getInstance()->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->field($fields)->find() : null;
    }

    /**
     * 查询商家商品
     * @author  WangJiang
     * @param array|string $shopIds 商铺ID，多个用','隔开
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|int $normId 规格ID
     * @param null|string $title 商品标题（模糊查询）
     * @param string $priceMin 商品售价下限
     * @param string $priceMax 商品售价上限
     * @param string|true|false $returnAlters 是否返回'alters'属性
     * @param int $pageSize 页面大小
     * @param int|null $status 状态
     * @return array
     */
    public function getProductList($shopIds,$categoryId, $brandId,$normId, $title
        ,$priceMin,$priceMax
        ,$returnAlters,$page, $pageSize){

        list($shopBindNames, $bindValues) = build_sql_bind($shopIds);

        $this->join('INNER JOIN sq_merchant_shop as shop on shop.id in ('.implode(',',$shopBindNames).') and shop.id=sq_merchant_depot.shop_id');

        $sql_pro='INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id';

        if(!empty($title)){
            $sql_pro.=' and pro.title like :title';
            $bindValues[':title']='%'.$title.'%';
        }

        $where='';
        if(!is_null($priceMin)){
            $where.='sq_merchant_depot.price>:priceMin';
            $bindValues[':priceMin']=$priceMin;
        }

        if(!is_null($priceMax)){
            if(!empty($where))
                $where=' and ';
            $where.='sq_merchant_depot.price<:priceMax';
            $bindValues[':priceMax']=$priceMax;
        }

        if(!empty($brandId)){
            //$sql->join('INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id and pro.brand_id=:brandId');
            $sql_pro.=' and pro.brand_id=:brandId';
            $bindValues[':brandId']=$brandId;
        }

        if(!empty($normId)){
            //$sql->join('INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id and pro.brand_id=:brandId');
            $sql_pro.=' and pro.norms_id=:normId';
            $bindValues[':normId']=$normId;
        }

        $this->join($sql_pro);

        if(!empty($categoryId)) {
            $this->join('INNER JOIN sq_product_category as pc on pc.category_id=:cateId AND pc.product_id=pro.id');
            $bindValues[':cateId']=$categoryId;
        }

        $this->field(['sq_merchant_depot.id','pro.id as product_id'
            ,'pro.title as product','sq_merchant_depot.price'
            ,'shop.id as shop_id','shop.title as shop']);

        if(!empty($where))
            $this->where($where);

        $this->bind($bindValues)->limit($page,$pageSize);

        $data=$this->select();

        //print_r($sql->getLastSql());

        $products=[];
        $depots=[];
        foreach($data as $i){
            $i['price'] = floatval($i['price']);
            $pid=$i['product_id'];
            if(!isset($products[$pid]))
                $products[$pid]=$i;

            if($returnAlters)
                $depots[$pid][]=$i;

            if($products[$pid]['price']>$i['price'])
                $products[$pid]=$i;
        }

        $ret=[];
        foreach($products as $k=>$product){
            if($returnAlters){
                $depot=$depots[$k];
                $alters=[];
                foreach($depot as $i){
                    if($product['id']!==$i['id'])
                        $alters[]=array('id'=>$i['id'],'price'=>$i['price'],'shop_id'=>$i['shop_id'],'shop'=>$i['shop']);
                }
                $product['alters']=$alters;
            }

            $ret[]=$product;
        }

        return $ret;
    }
}
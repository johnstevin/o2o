<?php
namespace Common\Model;

use Think\Model\RelationModel;
use Think\Page;

/**
 * 商家仓库模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @property integer $id
 * @property integer $shop_id 商铺ID
 * @property integer $product_id 商品ID
 * @property integer $status 状态
 * @property float $price 价格
 * @property integer $add_time 添加时间
 * @property integer $add_ip 添加IP
 * @property string $remark 备注
 * @property integer $update_time 更新时间
 * @property integer $update_ip 更新IP
 *
 * @package Common\Model
 */
class MerchantDepotModel extends RelationModel
{
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭

    protected $_link = [
    ];

    /**
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $fields = [
        'id',
        'shop_id',
        'product_id',
        'status',
        'price',
        'add_time',
        'add_ip',
        'remark',
        'update_time',
        'update_ip',
        '_type' => [
            'id' => 'int',
            'shop_id' => 'int',
            'product_id' => 'int',
            'price' => 'double',
            'add_time' => 'int',
            'add_ip' => 'char',
            'remark' => 'varchar',
            'update_time' => 'int',
            'update_ip' => 'char'
        ]
    ];
    protected $pk     = 'id';
    /**
     * 只读字段
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $readonlyField = ['shop_id', 'product_id', 'add_time', 'add_ip'];

    /**
     * 自动验证
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $_validate = [
        [
            'shop_id',
            'require',
            '商家ID不能为空',
            self::MUST_VALIDATE,
            '',
            self::MODEL_INSERT
        ],
        [
            'product_id',
            'require',
            '商品ID不能为空',
            self::MUST_VALIDATE,
            '',
            self::MODEL_INSERT
        ],
        [
            'product_id',
            'is_null',
            '商品ID不能修改',
            self::MUST_VALIDATE,
            'function',
            self::MODEL_UPDATE
        ],
        [
            'price',
            'currency',
            '分类不能为空',
            self::EXISTS_VALIDATE
        ],
        [
            'shop_id',
            'check_merchant_shop_exist',
            '有非法商家ID',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'product_id',
            'check_product_exist',
            '有非法商品ID',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'price',
            'currency',
            '价格非法',
            self::EXISTS_VALIDATE
        ],
        [
            'status',
            'number',
            '状态非法',
            self::EXISTS_VALIDATE
        ],
        [
            'status',
            [
                self::STATUS_CLOSE,
                self::STATUS_ACTIVE
            ],
            '状态的范围不正确',
            self::EXISTS_VALIDATE,
            'in'
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
     * 检测仓库里的商品是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param integer $id ID
     * @return bool
     */
    public static function checkDepotExist($id)
    {
        return self::getById($id, 'id') ? true : false;
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
        $where['shop_id'] = intval($merchantId);
        if (!$where['shop_id'] || !check_merchant_exist($where['shop_id'])) return ['data' => [], 'pagination' => ''];
        if (!empty($status)) $where['status'] = in_array($status, array_keys(self::getStatusOptions())) ? $status : self::STATUS_ACTIVE;
        $pageSize = intval($pageSize);
        $model = self::getInstance();
        $total = $model->where($where)->count('id');
        $result = [];
        $pagination = new Page($total, $pageSize);
        if ($total) {
            $data = $model->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
            $productIds = [];
            $depotProducts = [];
            foreach ($data as $key => $product) {
                $productIds[] = $product['product_id'];
                $depotProducts[$product['product_id']] = $product;
            }
            $productIds = array_unique($productIds);
            $products = ProductModel::getInstance()->where(['id' => ['IN', $productIds], 'status' => ProductModel::STATUS_ACTIVE])->select();
            $_products = [];
            foreach ($products as $key => $product) {
                $_products[$product['id']] = $product;
            }
            foreach ($_products as $key => $product) {
                if (isset($depotProducts[$key])) {
                    $result[$key] = $product;
                    $result[$key]['price'] = $depotProducts[$key]['price'];
                    $result[$key]['remark'] = $depotProducts[$key]['remark'];
                }
            }
        }
        return [
            'data' => $result,
            'pagination' => $pagination->show()
        ];
    }

    /**
     * 根据ID获取商家信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function getById($id, $fields = '*')
    {
        $id = intval($id);
        return $id ? self::getInstance()->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->field($fields)->find() : null;
    }

    /**
     * 过滤shopIds，返回groupIds允许访问的
     * @author WangJiang
     * @param $shopIds
     * @param $groupIds
     * @return array 过滤后的shopIds
     */
    private function _filter_shops($shopIds,$groupIds){

        if(empty($groupIds))
            return $shopIds;

        list($shopBindNames, $bindValues) = build_sql_bind($shopIds,[],'shopName');
        list($groupBindNames, $bindValues) = build_sql_bind($groupIds,$bindValues,'groupName');

        $mdl=M('MerchantShop')
            ->where('group_id in (' . implode(',', $groupBindNames) . ') and id in (' . implode(',', $shopBindNames) . ')')
            ->field(['id'])
            ->bind($bindValues);

        $ret= array_map(function($i){
            return $i['id'];
        },$mdl->select());
        //print_r($mdl->getLastSql());die;
        return $ret;
    }

    /**
     * 查询商家商品
     * @author  WangJiang
     * @param array $shopIds 商铺ID
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|int $normId 规格ID
     * @param null|string $title 商品标题（模糊查询）
     * @param null|int $priceMin 商品售价下限
     * @param null|int $priceMax 商品售价上限
     * @param string|true|false $returnAlters 是否返回'alters'属性
     * @param int $page 分页下标，从0开始
     * @param int $pageSize 页面大小
     * @param int $status 查询状态，-1：逻辑删除,0:不可用，1：可用，为空返回所有状态
     * @param array $groupIds 登录用户分组，用来检查用户对shopIds的访问权限，为空则不检查。
     * @return array
     */
    public function getProductList($shopIds,$categoryId, $brandId,$normId, $title
        ,$priceMin,$priceMax
        ,$returnAlters,$page, $pageSize,$status=self::STATUS_ACTIVE,$groupIds=[])
    {
        $shopIds=$this->_filter_shops($shopIds,$groupIds);
        //print_r($shopIds);die;

        list($shopBindNames, $bindValues) = build_sql_bind($shopIds);

        $this->join('INNER JOIN sq_merchant_shop as shop on shop.id in (' . implode(',', $shopBindNames) . ') and shop.id=sq_merchant_depot.shop_id');

        $sql_pro = 'INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id and pro.status=1';

        if (!empty($title)) {
            $sql_pro .= ' and pro.title like :title';
            $bindValues[':title'] = '%' . $title . '%';
        }

        $where='';
        if(array_key_exists($status,self::getStatusOptions())) {
            $where = 'sq_merchant_depot.status=:statusName';
            $bindValues[':statusName'] = $status;
        }

        if (!is_null($priceMin)) {
            if(!empty($where))
                $where = ' and ';
            $where .= 'sq_merchant_depot.price>:priceMin';
            $bindValues[':priceMin'] = $priceMin;
        }

        if (!is_null($priceMax)) {
            if(!empty($where))
                $where = ' and ';
            $where .= 'sq_merchant_depot.price<:priceMax';
            $bindValues[':priceMax'] = $priceMax;
        }

        if (!empty($brandId)) {
            $sql_pro .= ' and pro.brand_id=:brandId';
            $bindValues[':brandId'] = $brandId;
        }

        if (!empty($normId)) {
            $sql_pro .= ' and pro.norms_id=:normId';
            $bindValues[':normId'] = $normId;
        }

        $sql_pro .= ' LEFT JOIN sq_brand as brand on brand.id=pro.brand_id';
        $sql_pro .= ' LEFT JOIN sq_norms as norm on norm.id=pro.norms_id';

        $this->join($sql_pro);

        if (!empty($categoryId)) {
            $this->join('INNER JOIN sq_product_category as pc on pc.category_id=:cateId AND pc.product_id=pro.id');
            $bindValues[':cateId'] = $categoryId;
        }

        $this->field(['sq_merchant_depot.id', 'pro.id as product_id'
            , 'pro.title as product', 'sq_merchant_depot.price'
            , 'shop.id as shop_id', 'shop.title as shop', 'brand.id as brand_id','brand.title as brand', 'norm.id as norm_id','norm.title as norm']);

        if (!empty($where))
            $this->where($where);

        $this->bind($bindValues)->limit($page, $pageSize);

        $data = $this->select();

        //print_r($this->getLastSql());die;

        $products = [];
        $depots = [];
        foreach ($data as $i) {
            $i['price'] = floatval($i['price']);
            $pid = $i['product_id'];
            if (!isset($products[$pid]))
                $products[$pid] = $i;

            if ($returnAlters)
                $depots[$pid][] = $i;

            if ($products[$pid]['price'] > $i['price'])
                $products[$pid] = $i;
        }

        $ret = [];
        foreach ($products as $k => $product) {
            if ($returnAlters) {
                $depot = $depots[$k];
                $alters = [];
                foreach ($depot as $i) {
                    if ($product['id'] !== $i['id'])
                        $alters[] = array('id' => $i['id'], 'price' => $i['price'], 'shop_id' => $i['shop_id'], 'shop' => $i['shop']);
                }
                $product['alters'] = $alters;
            }

            $ret[] = $product;
        }

        return $ret;
    }

    /**
     * 获得单条商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|integer $shopId
     * @param null|integer $productId
     * @param null|integer $status
     * @param string|string|array $fileds
     * @return null|array
     */
    public static function get($shopId = null, $productId = null, $status = null, $fileds = '*')
    {
        $where = [];
        if (!empty($shopId)) $where['shop_id'] = intval($shopId);
        if (!empty($productId)) $where['product_id'] = intval($productId);
        if ($status !== null && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = self::STATUS_ACTIVE;
        }
        return self::getInstance()->field($fileds)->where($where)->find() ?: null;
    }

    /**
     * 添加仓库商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param integer $shopId 商铺ID
     * @param integer $productId 商品ID
     * @param null|float|integer $price 价格
     * @param string $remark 备注
     * @return bool|integer
     */
    public static function addDepot($shopId, $productId, $price = null, $remark = '')
    {
        if ($depot = self::get($shopId, $productId, self::STATUS_CLOSE)) {
            $data['id'] = $depot['id'];
            $data['status'] = self::STATUS_ACTIVE;
        }
        $data['shop_id'] = intval($shopId);
        $data['product_id'] = intval($productId);
        $data['remark'] = trim($remark);
        $product = ProductModel::get($productId, ['price', 'id'], '_categorys');
        $data['price'] = empty($price) && $product ? (float)$product['price'] : (float)$price;
        $model = self::getInstance();
        if (!$model->create($data))
            E($model->getError());
        return isset($data['id']) ? $model->where(['id' => $data['id']])->save() : $model->add() && self::addMerchantDepotCategory($shopId, $product['_categorys']);
    }

    /**
     * 更新仓库的商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param integer $id ID
     * @param null|int $status 状态
     * @param null|float $price 价格
     * @param null|string $remark 备注
     * @param null|int $productId 商品ID
     * @return bool
     */
    public static function updateDepot($id, $status = null, $price = null, $remark = null, $productId = null)
    {
        if (!self::checkDepotExist($id)) E('没有找到ID：' . $id . '的仓库商品');
        $model = self::getInstance();
        $data['id'] = $id;
        if (!empty($status) && in_array($status, array_keys(self::getStatusOptions()))) $data['status'] = $status;
        if (!empty($price)) $data['status'] = $price;
        if (!empty($remark)) $data['remark'] = $remark;
        if ($model->create() && $model->save()) return true;
        E($model->getError());
    }

    /**
     * 添加商家商品的分类
     * @param int $shopId 商家ID
     * @param int|string $categoryId 分类ID
     * @return bool
     */
    public static function addMerchantDepotCategory($shopId, $categoryId)
    {
        $model = M('merchant_depot_pro_category');
        $ids = is_array($categoryId) ?: explode(',', $categoryId);
        $ids = array_unique($ids);
        foreach ($ids as $id) {
            if (!$model->where(['shop_id' => $shopId, 'category_id' => $id])->find()) {
                $model->shop_id = $shopId;
                $model->category_id = $id;
                $model->add();
            }
        }
        return true;
    }

    /**
     * 逻辑删除商品
     * @param int $id
     * @return bool|void
     */
    public static function deleteDepot($id)
    {
        $id = intval($id);
        return $id ? self::getInstance()->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->save(['status' => self::STATUS_CLOSE]) : E('仓库商品ID非法');
    }
}
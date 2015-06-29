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
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭
    const STATUS_VERIFY = 2;//待审核

    protected $_link = [
        'Product' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Product',
            'foreign_key' => 'product_id',
            'mapping_name' => '_product',
        ]
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
    protected $pk = 'id';

    /**
     * 只读字段
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $readonlyField = ['shop_id', 'product_id', 'add_time', 'add_ip'];

    protected function checkReadonlyField(&$data)
    {
        if (!empty($this->readonlyField)) {
            foreach ($this->readonlyField as $key => $field) {
                if (isset($data[$field]))
                    unset($data[$field]);
            }
        }
        return $data;
    }

    // 更新前的回调方法
    protected function _before_update(&$data, $options = '')
    {
        // 检查只读字段
        $data = $this->checkReadonlyField($data);
    }

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
            'check_shop_exist',
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
                self::STATUS_DELETE,
                self::STATUS_CLOSE,
                self::STATUS_ACTIVE,
                self::STATUS_VERIFY,
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
            self::STATUS_VERIFY,
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
            self::STATUS_DELETE => '逻辑删除',
            self::STATUS_CLOSE => '关闭',
            self::STATUS_ACTIVE => '正常',
            self::STATUS_VERIFY => '待审核'
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
     * @param int $shopId 商家ID
     * @param int $pageSize 页面大小
     * @param int $status 商品状态
     * @return array
     */
    public static function getListsByShopId($shopId, $pageSize = 10, $status = self::STATUS_ACTIVE)
    {
        $where['shop_id'] = intval($shopId);
        //检查商铺是否存在
        if (!$where['shop_id'] || !check_merchant_exist($where['shop_id'])) return ['data' => [], 'pagination' => ''];
        //如果状态不为空并且状态在系统状态里，那么状态就为传入的状态，否则就为正常状态
        if ($status !== null && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = self::STATUS_ACTIVE;
        }
        $pageSize = intval($pageSize);
        $model = self::getInstance();
        $total = $model->where($where)->count('id');//获得总记录数
        $pagination = new Page($total, $pageSize);
        if ($total) {//如果统计数大于1
            $data = $model->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
            $productIds = [];
            $depotProducts = [];
            foreach ($data as $key => $product) {
                $productIds[] = $product['product_id'];//获取所有的商品ID
                $depotProducts[$product['product_id']] = &$product;//重新组合数组
            }
            $productIds = array_unique($productIds);
            $products = ProductModel::getInstance()->where(['id' => ['IN', $productIds], 'status' => ProductModel::STATUS_ACTIVE])->select();
            $_products = [];
            foreach ($products as $key => $product) {
                $_products[$product['id']] = $product;//重新组合数组
            }
            foreach ($depotProducts as $key => &$product) {
                if (isset($_products[$key])) {//如果系统商品里的这个商品存在，则把系统总库的商品标题和介绍存入要返回的数组里
                    $product['title'] = &$_products[$key]['title'];
                    $product['detail'] = &$_products[$key]['detail'];
                }
            }
            return [
                'data' => $depotProducts,
                'pagination' => $pagination->show()
            ];
        }
        return [
            'data' => [],
            'pagination' => ''
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
    private function _filter_shops($shopIds, $groupIds)
    {

        if (empty($groupIds))
            return $shopIds;

        list($shopBindNames, $bindValues) = build_sql_bind($shopIds, [], 'shopName');
        list($groupBindNames, $bindValues) = build_sql_bind($groupIds, $bindValues, 'groupName');

        $mdl = M('MerchantShop')
            ->where('group_id in (' . implode(',', $groupBindNames) . ') and id in (' . implode(',', $shopBindNames) . ')')
            ->field(['id'])
            ->bind($bindValues);

        $ret = array_map(function ($i) {
            return $i['id'];
        }, $mdl->select());
        //print_r($mdl->getLastSql());die;
        return $ret;
    }

    public function getProductList($shopIds, $categoryId, $brandId, $normId, $title
        , $priceMin, $priceMax
        , $returnAlters, $page, $pageSize, $status = self::STATUS_ACTIVE, $groupIds = [])
    {

        $shopIds = $this->_filter_shops($shopIds, $groupIds);
        list($shopBindNames, $bindValues) = build_sql_bind($shopIds);

        $where = 'sq_merchant_depot.shop_id in (' . implode(',', $shopBindNames) . ')';

        $cateChain=[$categoryId];
        get_cate_chain_up([$categoryId],$cateChain);

        if (!empty($categoryId)) {
            $where .= ' and sq_merchant_depot.status=1 and sq_merchant_depot.product_id
                in (select product_id from sq_product_category where category_id in (:cateIds))';
            $bindValues[':cateIds'] = $cateChain;
        }
        if (!is_null($priceMin)) {
            $where .= ' and sq_merchant_depot.price>:priceMin';
            $bindValues[':priceMin'] = $priceMin;
        }
        if (!is_null($priceMax)) {
            $where .= ' and sq_merchant_depot.price<:priceMax';
            $bindValues[':priceMax'] = $priceMax;
        }

        //TODO 加上品牌规格查询
        $product_sql = 'JOIN sq_product on sq_product.id = sq_merchant_depot.product_id and sq_product.status=1';
        if (!empty($categoryId)) {
            $product_sql .= ' and sq_product.id in (select product_id from sq_product_category where category_id=:categoryId)';
            $bindValues[':categoryId'] = $categoryId;
        }
        if (!empty($title)) {
            $product_sql .= ' and sq_product.title like :title';
            $bindValues[':title'] = '%' . $title . '%';
        }
        if (!empty($brandId)) {
            $product_sql .= ' and sq_product.brand_id=:brandId';
            $bindValues[':brandId'] = $brandId;
        }
        if (!empty($normId)) {
            $product_sql .= ' and sq_product.norms_id=:normId';
            $bindValues[':normId'] = $normId;
        }
        $this->join($product_sql);
        //$this->join('JOIN sq_merchant_shop on sq_merchant_shop.id=sq_merchant_depot.shop_id');
        $this->join('JOIN sq_brand as brand on brand.id=sq_product.brand_id');
        $this->join('JOIN sq_norms as norm on norm.id=sq_product.norms_id');
        $this->join('left JOIN sq_picture on sq_picture.id = sq_product.picture');

        $this->where($where);
        $this->bind($bindValues);

        $this->field(['sq_merchant_depot.product_id'
            , 'sq_product.title as product', 'sq_merchant_depot.status', 'sq_product.description', 'brand.id as brand_id'
            , 'brand.title as brand', 'norm.id as norm_id', 'norm.title as norm', 'ifnull(sq_picture.path,\'\') as picture_path', 'sq_product.picture as picture_id']);

        $this->group('sq_merchant_depot.product_id')
            //->order('sq_merchant_depot.product_id,sq_merchant_depot.price')
            ->limit($page, $pageSize);
        //echo '<pre>';
        //var_dump($bindValues);die;
        $data = $this->select();
        //print_r($this->getLastSql());die;

        foreach ($data as &$dpt) {
            list($shopBindNames, $bindValues) = build_sql_bind($shopIds);
            $this->field(['sq_merchant_depot.id'
                , 'sq_merchant_depot.price'
                , 'sq_merchant_shop.id as shop_id'
                , 'sq_merchant_shop.title as shop']);
            $this->join('JOIN sq_merchant_shop on sq_merchant_shop.id=sq_merchant_depot.shop_id');
            $this->where('shop_id in (' . implode(',', $shopBindNames) . ') and product_id =:productId');
            $bindValues[':productId'] = $dpt['product_id'];
            $this->bind($bindValues);
            $alters = $this->select();

            $minPrice = null;
            $maxPrice = null;
            $depot = null;
            foreach ($alters as &$al) {
                if (is_null($minPrice))
                    $minPrice = $al['price'];
                else
                    $minPrice = min($minPrice, $al['price']);

                if (is_null($maxPrice))
                    $maxPrice = $al['price'];
                else
                    $maxPrice = max($maxPrice, $al['price']);
                if (is_null($depot))
                    $depot = $al;
                if ($al['price'] <= $minPrice)
                    $depot = $al;
            }
            $dpt['id'] = $depot['id'];
            $dpt['price'] = $depot['price'];
            $dpt['shop_id'] = $depot['shop_id'];
            $dpt['shop'] = $depot['shop'];
            $dpt['price_range'] = [$minPrice, $maxPrice];
            $dpt['alters'] = $alters;
        }
        return $data;
    }

    /**
     * @ignore
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
    public function getProductList_($shopIds, $categoryId, $brandId, $normId, $title
        , $priceMin, $priceMax
        , $returnAlters, $page, $pageSize, $status = self::STATUS_ACTIVE, $groupIds = [])
    {
        $shopIds = $this->_filter_shops($shopIds, $groupIds);
        //print_r($shopIds);die;

        list($shopBindNames, $bindValues) = build_sql_bind($shopIds);

        $this->join('INNER JOIN sq_merchant_shop as shop on shop.id in (' . implode(',', $shopBindNames) . ') and shop.id=sq_merchant_depot.shop_id');

        $sql_pro = 'INNER JOIN sq_product as pro on pro.status=1';

        if (!empty($title)) {
            $sql_pro .= ' and pro.title like :title';
            $bindValues[':title'] = '%' . $title . '%';
        }

        $where = '';
        if (array_key_exists($status, self::getStatusOptions())) {
            $where = 'sq_merchant_depot.status=:statusName';
            $bindValues[':statusName'] = $status;
        }

        if (!is_null($priceMin)) {
            if (!empty($where))
                $where = ' and ';
            $where .= 'sq_merchant_depot.price>:priceMin';
            $bindValues[':priceMin'] = $priceMin;
        }

        if (!is_null($priceMax)) {
            if (!empty($where))
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

        $this->field(['sq_merchant_depot.id', 'pro.id as product_id', 'pro.description'
            , 'pro.title as product', 'sq_merchant_depot.price', 'sq_merchant_depot.add_time'
            , 'shop.id as shop_id', 'shop.title as shop', 'brand.id as brand_id'
            , 'brand.title as brand', 'norm.id as norm_id', 'norm.title as norm']);

        if (!empty($where))
            $this->where($where);

        $this->bind($bindValues)->limit($page, $pageSize)->order('sq_merchant_depot.price');

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
                $minPrice = null;
                $maxPrice = null;
                foreach ($depot as $i) {
                    if ($product['id'] !== $i['id'])
                        $alters[] = ['id' => $i['id'], 'price' => $i['price'], 'shop_id' => $i['shop_id'], 'shop' => $i['shop']];
                    if (is_null($minPrice))
                        $minPrice = $i['price'];
                    else
                        $minPrice = min($minPrice, $i['price']);
                    if (is_null($maxPrice))
                        $maxPrice = $i['price'];
                    else
                        $maxPrice = max($maxPrice, $i['price']);
                }
                $product['price_range'] = [$minPrice, $maxPrice];
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
        if ($data = self::getInstance()->relation(true)->field($fileds)->where($where)->find()) {
            $data['detail'] = $data['_product']['detail'];
            $data['title'] = $data['_product']['title'];
            $data['brand_id'] = $data['_product']['brand_id'];
        }
        return $data;
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
     * @throws \Exception
     */
    public static function addDepot($shopId, $productId, $price = null, $remark = '')
    {
        //如果在已关闭的数据里，则重新开启这个商品
        if ($depot = self::get($shopId, $productId, self::STATUS_CLOSE)) {
            $data['id'] = $depot['id'];
            $data['status'] = self::STATUS_ACTIVE;
        }
        $data['shop_id'] = intval($shopId);
        $data['product_id'] = intval($productId);
        $data['remark'] = trim($remark);
        //获取产品信息和所属分类
        $product = ProductModel::get($productId, ['price', 'id'], true);
        $data['price'] = empty($price) && $product ? (float)$product['price'] : (float)$price;

        $depotCategoryModel = M('merchant_depot_pro_category');
        $ids = is_array($product['_categorys']) ?: explode(',', $product['_categorys']);
        $ids = array_unique($ids);
        $categoris = [];
        foreach ($ids as $id) {
            //如果没有找到相应的分类才添加
            if (!$depotCategoryModel->where(['shop_id' => $shopId, 'category_id' => $id])->find()) {
                $categoris[] = [
                    'shop_id' => $shopId,
                    'category_id' => $id
                ];
            }
        }
        $model = self::getInstance();
        if (!$model->create($data))
            E(current($model->getError()));
        $model->startTrans();//启动事务
        try {
            isset($data['id']) ? $status = $model->where(['id' => $data['id']])->save() : $status = $model->add();
            if ($status && $depotCategoryModel->addAll($categoris)) {
                $model->commit();//提交事务
                return $model->getLastInsID();
            } else {
                E('添加或更新商品失败');
            }
        } catch (\Exception $e) {
            $model->rollback();//回滚事务
            throw $e;
        }
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
        if (!empty($productId)) $data['product_id'] = intval($productId);
        if (!$model->create($data)) E(is_array($model->getError()) ? current($model->getError()) : $model->getError());
        return $model->save();
    }

    /**
     * 添加商家商品的分类
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $shopId 商家ID
     * @param int|string $categoryId 分类ID
     * @return bool
     */
    public static function addMerchantDepotCategory($shopId, $categoryId)
    {
        //TODO 这个方法暂时没有地方调用它，可酌情删除
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
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     * @param bool $logic 是否逻辑删除
     * @return bool|int
     */
    public static function deleteDepot($id, $logic = true)
    {
        $id = intval($id);
        if (!$id) E('仓库商品ID非法');
        if ($logic) {
            return self::getInstance()->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->save(['status' => self::STATUS_CLOSE]);
        }
        return self::getInstance()->delete($id);
    }

    /**
     * 根据ID获取列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string|array $ids
     * @param bool $getCategorys 是否获得分类信息
     * @param bool $getBrand 是否获得品牌信息
     * @param bool $getNorm 是否获得规格信息
     * @return null|array
     */
    public function getListsByIds($ids, $getCategorys = false, $getBrand = false, $getNorm = false)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $ids = array_unique($ids);
        $model = self::getInstance()->alias('md');
        $where = [
            'id' => [
                'IN',
                $ids
            ],
        ];
        $fields = [
            'md.id',
            'md.shop_id',
            'md.product_id',
            'md.status',
            'md.price',
            'md.remark',
            'p.title',
            'p.brand_id',
            'p.norms_id',
            'p.detail',
            'p.number',
            'p.description',
            'p.message'
        ];
        $model->join('LEFT JOIN' . ProductModel::getInstance()->getTableName() . ' p ON d.product_id=p.id');
        $data = $model->where($where)->field($fields)->select();
        return $data;
    }

    /**
     * 商品下架，只能下架【已上架】的商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int|array|string $ids depot IDs 可传多个ID
     * @return bool
     */
    public function offShelf($ids)
    {
        if (!is_array($ids)) $ids = explode(',', $ids);
        $ids = array_unique($ids);
        return self::getInstance()->where([
            'id' => ['IN', $ids],
            'status' => self::STATUS_ACTIVE
        ])->save(['status' => self::STATUS_CLOSE]);
    }

    /**
     * 商品上架，只能上架状态为【已下架】的商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int|string|array $ids 商品仓库ID
     * @return bool
     */
    public function onShelf($ids)
    {
        if (!is_array($ids)) $ids = explode(',', $ids);
        $ids = array_unique($ids);
        return self::getInstance()->where([
            'id' => ['IN', $ids],
            'status' => self::STATUS_CLOSE
        ])->save(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * 获取仓库商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $shopId 商铺ID
     * @param null|string|array $categoryIds 分类ID
     * @param null|int $status 状态
     * @param null|string $title 标题
     * @param string $sort 创建时间排序，可传【asc】和【desc】
     * @param int $pageSize 分页大小
     * @param bool $getPicture 是否获产品得图片
     * @param bool $getCategorys 是否获得分类信息
     * @param bool $getBrand 是否获得品牌信息
     * @param bool $getNorm 是否获得规格信息
     * @param bool $getShop 是否获得商铺信息
     * @return array
     */
    public function getLists($shopId = null, $categoryIds = null, $status = null, $title = null, $sort = 'asc', $pageSize = 20, $getPicture = false, $getCategorys = false, $getBrand = false, $getNorm = false, $getShop = false)
    {
        $nowPage = $_GET['p'] ? intval($_GET['p']) : 1;
        $where = [
            'p.status' => ProductModel::STATUS_ACTIVE
        ];
        $sort = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';
        $fields = [//要查询的字段
            'md.id',
            'md.shop_id',
            'md.product_id',
            'md.status',
            'md.price',
            'md.remark',
            'md.add_time',
            'p.title',
            'p.detail',
            'p.number',
            'p.description',
            'p.message',
        ];
        $model = self::getInstance()->alias('md');//给当前表取一个别名
        $model->join('LEFT JOIN sq_product p ON md.product_id=p.id');
        if ($getPicture) {//如果需要获取图片
            $model->join('LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id');
            $fields = array_merge($fields, [
                'product_picture.id picture_id',
                'product_picture.path picture_path'
            ]);
        }
        //如果状态不为null并且状态在系统状态列表里则读取指定状态的数据，否则读取上架或者下架的数据
        if ($status !== null && array_key_exists($status, self::getStatusOptions())) {
            $where['md.status'] = intval($status);
        } else {
            $where['md.status'] = [
                'IN',
                [
                    self::STATUS_ACTIVE,
                    self::STATUS_CLOSE
                ]
            ];
        }
        if (!empty($shopId)) {
            $where['md.shop_id'] = intval($shopId);
        }
        if (!empty($categoryIds)) {
            $categoryIds = array_unique(is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds));
            $model->join('LEFT JOIN sq_product_category pc ON md.product_id = pc.product_id');
            $where['pc.category_id'] = [
                'IN',
                $categoryIds
            ];
        }
        if (!empty($title) && is_string($title)) {
            $where['p.title'] = [
                'LIKE',
                '%' . trim($title) . '%'
            ];
        }
        if ($getBrand) {
            $model->join('LEFT JOIN sq_brand brand ON brand.id=p.brand_id');
            $fields = array_merge($fields, [
                'brand.title _brand_title',
                'brand.id _brand_id',
                'brand.description _brand_description'
            ]);
        }
        if ($getNorm) {
            $model->join('LEFT JOIN sq_norms norm ON p.norms_id=norm.id');
            $fields = array_merge($fields, [
                'norm.title _norm_title',
                'norm.id _norm_id'
            ]);
        }
        if ($getShop) {
            $model->join('LEFT JOIN sq_merchant_shop shop ON md.shop_id=shop.id');
            $fields = array_merge($fields, [
                'shop.id _shop_id',
                'shop.title _shop_title',
                'shop.address _shop_address',
                'shop.status _shop_status',
                'shop.phone_number _shop_phone'
            ]);
        }
        $model->field($fields)->where($where)->order('md.add_time ' . $sort);
        $totalModel = clone $model;
        $list = $model->page($nowPage, $pageSize)->select();
        $total = $totalModel->count('*');

        if ($getBrand || $getCategorys || $getShop || $getNorm) {
            $pdo = get_pdo();
            foreach ($list as &$item) {//循环列表并且整合数据

                if ($getBrand) {
                    $item['_brand'] = [
                        'id' => $item['_brand_id'],
                        'title' => $item['_brand_title'],
                        'description' => $item['_brand_description']
                    ];
                    unset($item['_brand_id'], $item['_brand_title'], $item['_brand_description']);
                }

                if ($getNorm) {
                    $item['_norm'] = [
                        'id' => $item['_norm_id'],
                        'title' => $item['_norm_title']
                    ];
                    unset($item['_norm_id'], $item['_norm_title']);
                }

                if ($getShop) {
                    $item['_shop'] = [
                        'id' => $item['_shop_id'],
                        'title' => $item['_shop_title'],
                        'address' => $item['_shop_address'],
                        'status' => $item['_shop_status'],
                        'phone' => $item['_shop_phone']
                    ];
                    unset($item['_shop_id'], $item['_shop_title'], $item['_shop_address'], $item['_shop_status'], $item['_shop_phone']);
                }

                if ($getCategorys) {
                    $sth = $pdo->prepare('SELECT c.id,c.title,c.pid,c.icon,c.level FROM sq_category c WHERE id IN (SELECT category_id FROM sq_product_category WHERE product_id=' . $item['product_id'] . ')');
                    $sth->execute();
                    $item['_categorys'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
                }
            }
        }
        return [
            'total' => $total,
            'data' => $list
        ];
    }

    /**
     * 根据商品条形码获取商品信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $number 条形码
     * @param null|int $shopId 商铺ID，可选。默认为查出所有有这个商品的店家
     * @param bool $getPicture 是否获得商品图片信息
     * @param bool $getCategorys 是否获得分类信息
     * @param bool $getBrand 是否获得品牌信息
     * @param bool $getNorm 是否获得规格信息
     * @param bool $getShop 是否获得商铺信息
     * @return array
     */
    public function getByNumber($number, $shopId = null, $getPicture = false, $getCategorys = false, $getBrand = false, $getNorm = false, $getShop = false)
    {
        $where = [
            'p.number' => $number,
            'p.status' => ProductModel::STATUS_ACTIVE
        ];
        if ($shopId) {
            $where['md.shop_id'] = intval($shopId);
        }
        $fields = [
            'md.id',
            'md.shop_id',
            'md.product_id',
            'md.status',
            'md.price',
            'md.remark',
            'md.add_time',
            'p.title',
            'p.brand_id',
            'p.norms_id',
            'p.number',
            'p.description',
            'p.detail'
        ];
        $model = $this->getInstance()->join('LEFT JOIN sq_product p ON p.id=md.product_id');
        if ($getPicture) {
            $fields = array_merge($fields, [
                'p_picture.path picture_path',
                'p_picture.id picture_id'
            ]);
            $model->join('LEFT JOIN sq_picture p_picture ON p.picture=p_picture.id');
        }
        if ($getBrand) {
            $fields = array_merge($fields, [
                'b.id _brand_id',
                'b.title _brand_title',
                'b.description _brand_description'
            ]);
            $model->join('LEFT JOIN sq_brand b ON p.brand_id=b.id');
            $where['b.status'] = BrandModel::STATUS_ACTIVE;
        }
        if ($getNorm) {
            $fields = array_merge($fields, [
                'n.id _norm_id',
                'n.title _norm_title'
            ]);
            $model->join('LEFT JOIN sq_norms n ON n.id=p.norms_id');
        }
        if ($getShop) {
            $model->join('LEFT JOIN sq_merchant_shop ms ON ms.id=md.shop_id');
            $fields = array_merge($fields, [
                'ms.id _shop_id',
                'ms.title _shop_title',
                'ms.status _shop_status',
                'ms.phone_number _shop_phone',
                'ms.address _shop_address',
                'ms.region_id _shop_region_id'
            ]);
        }
        $lists = $model->alias('md')->field($fields)->where($where)->select();
        if ($getBrand || $getNorm || $getCategorys || $getPicture || $getShop) {
            $pdo = get_pdo();
            foreach ($lists as &$item) {
                if ($getBrand) {
                    $item['_brand'] = [
                        'id' => $item['_brand_id'],
                        'title' => $item['_brand_title'],
                        'description' => $item['_brand_description']
                    ];
                    unset($item['_brand_id'], $item['_brand_title'], $item['_brand_description']);
                }
                if ($getNorm) {
                    $item['_norm'] = [
                        'id' => $item['_norm_id'],
                        'title' => $item['_norm_title']
                    ];
                    unset($item['_norm_id'], $item['_norm_title']);
                }
                if ($getShop) {
                    $item['_shop'] = [
                        'id' => $item['_shop_id'],
                        'title' => $item['_shop_title'],
                        'address' => $item['_shop_address'],
                        'status' => $item['_shop_status'],
                        'phone' => $item['_shop_phone'],
                        'type' => $item['_shop_type'],
                        'region_id' => $item['_shop_region_id'],
                    ];
                    unset($item['_shop_id'], $item['_shop_title'], $item['_shop_address'], $item['_shop_status'], $item['_shop_phone'], $item['_shop_region_id']);
                }
                if ($getCategorys) {
                    $sth = $pdo->prepare('SELECT c.id,c.title,c.pid,c.icon,c.level FROM sq_category c WHERE id IN (SELECT category_id FROM sq_product_category WHERE product_id=' . $item['product_id'] . ')');
                    $sth->execute();
                    $item['_categorys'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
                }
            }

        }
        return [
            'data' => $lists
        ];
    }
}
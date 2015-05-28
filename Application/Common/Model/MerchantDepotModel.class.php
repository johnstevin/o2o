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
            'price',
            'currency',
            '分类不能为空',
            self::EXISTS_VALIDATE
        ],
        [
            'shop_id',
            'check_merchant_exist',
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
<?php
namespace Common\Model;

use Think\Exception;
use Think\Model\AdvModel;
use Think\Page;

/**
 * 系统商品模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class ProductModel extends AdvModel
{
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭

    //模型的字段
    protected $fields = [
        'id',
        'title',
        'brand_id',
        'price',
        'detail',
        'add_time',
        'add_ip',
        'edit_time',
        'status',
        '_type' => [
            'id' => 'int',
            'title' => 'varchar',
            'cate_id' => 'varchar',
            'brand_id' => 'int',
            'price' => 'decimal',
            'detail' => 'longtext',
            'add_time' => 'int',
            'add_ip' => 'bigint',
            'edit_time' => 'int',
            'edit_ip' => 'bigint',
            'status' => 'tinyint'
        ]
    ];
    /**
     * 只读字段
     * @var array
     */
    protected $readonlyField = ['id', 'add_time', 'add_ip'];

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
    ];

    /**
     * 自动完成
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
            'edit_time',
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
            'edit_ip',
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
     * 获取当前模型实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return ProductModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    protected function _after_find(&$result, $options = '')
    {
        parent::_after_find($result, $options);
        $result['_status'] = self::getStatusOptions()[$result['status']];
        $result['_add_time'] = date(C('DATE_FORMAT'), $result['add_time']);
        $result['_edit_time'] = date(C('DATE_FORMAT'), $result['edit_time']);
    }

    protected function _after_select(&$result, $options = '')
    {
        parent::_after_select($result, $options);
        foreach ($result as &$value) {
            $value['_status'] = self::getStatusOptions()[$result['status']];
            $value['_add_time'] = date(C('DATE_FORMAT'), $result['add_time']);
            $value['_edit_time'] = date(C('DATE_FORMAT'), $result['edit_time']);
        }
    }

    /**
     * 检测商品是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 商品ID
     * @return bool
     */
    public static function checkProductExist($id)
    {
        $id = intval($id);
        return ($id && self::get($id, 'id')) ? true : false;
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
     * 获取商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|string|array|int $categoryIds 分类ID，可传数组、NULL、以逗号分割的ID号或单个ID
     * @param null|string|array|int $brandId 品牌ID，可传数组、NULL、以逗号分割的ID号或单个ID
     * @param null|int $status 状态，可传null或数字
     * @param null|string $title 商品标题，用于模糊搜索，可传NULL
     * @param int $pageSize 分页大小，默认为10
     * @param array|string $relation 要进行关联查询的表，用逗号分割或者直接数组，可关联category、brand
     * @return array
     */
    public static function getLists($categoryIds = null, $brandId = null, $status = self::STATUS_ACTIVE, $title = null, $pageSize = 10, $relation = [])
    {
        $where = [];
        $productIds = null;
        $relation = is_array($relation) ? $relation : explode(',', $relation);
        if (!empty($categoryIds)) {//如果分类ID不为空，则先查询出分类下所有的商品ID
            $categoryIds = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
            $categoryIds = array_unique($categoryIds);
            $categoryProducts = M('product_category')->where(['category_id' => ['IN', $categoryIds]])->select();
            if (empty($categoryProducts)) {
                return [
                    'data' => [],
                    'pagination' => ''
                ];
            }
            $productIds = array_map(function ($value) {
                return $value['product_id'];
            }, $categoryProducts);
        }
        if (!empty($brandId)) {
            $brandId = is_array($brandId) ? $brandId : explode(',', $brandId);
            $where['brands'] = ['IN', array_unique($brandId)];
        }
        if (!empty($status)) $where['status'] = in_array($status, array_keys(self::getStatusOptions())) ? $status : self::STATUS_ACTIVE;
        if (!empty($title)) $where['title'] = ['LIKE', trim($title)];
        $model = self::getInstance()->alias('p');
        if ($productIds) {
            $subSql = $model->where(['id' => ['IN', $productIds]])->buildSql();
            $total = $model->table($subSql . ' sub')->where($where)->count('id');
            $pagination = new Page($total, $pageSize);
            $data = $model->table($subSql . ' sub')->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        } else {
            $total = $model->where($where)->count('id');
            $pagination = new Page($total, $pageSize);
            $data = $model->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        }
        if (array_search('category', $relation) !== false) self::relationCategory($data);//关联分类
        if (array_search('brand', $relation) !== false) self::relationBrand($data);//关联品牌
        return [
            'data' => $data,
            'pagination' => $pagination->show()
        ];
    }

    /**
     * 根据获取单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id ID
     * @param string|array $fields 要查询的字段
     * @return null|array
     */
    public static function get($id, $fields = '*')
    {
        $id = intval($id);
        return $id ? self::getInstance()->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->field($fields)->find() : null;
    }

    /**
     * 关联分类
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param array $data 查询出来的数组
     * @param bool $isList 是否是多个产品
     */
    public static function relationCategory(&$data, $isList = true)
    {
        $categoryModel = CategoryModel::getInstance();
        $productCategoryModel = M('product_category');
        if ($isList) {
            foreach ($data as &$value) {
                $categorys = $productCategoryModel->field('category_id')->where(['product_id' => $value['id']])->select();
                $categoryIds = array_map(function ($category) {
                    return $category['category_id'];
                }, $categorys);
                $value['categorys'] = $categoryModel->where(['id' => ['IN', $categoryIds]])->select();
            }
        } else {
            $categorys = $productCategoryModel->field('category_id')->where(['product_id' => $data['id']])->select();
            $categoryIds = array_map(function ($category) {
                return $category['category_id'];
            }, $categorys);
            $data['categorys'] = $categoryModel->where(['id' => ['IN', $categoryIds]]);
        }
    }

    /**
     * 关联品牌
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param array $data 查询出来的产品数组
     * @param bool $isList 是否有多个产品
     */
    public static function relationBrand(&$data, $isList = true)
    {
        $brandModel = BrandModel::getInstance();
        if ($isList) {
            foreach ($data as &$value) {
                $value['brand'] = $brandModel->find($value['brand_id']);
            }
        } else {
            $data['brand'] = $brandModel->find($data['brand_id']);;
        }
    }
}
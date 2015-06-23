<?php
namespace Common\Model;

use Think\Model\RelationModel;
use Think\Page;

/**
 * 系统商品模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class ProductModel extends RelationModel
{
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭
    const STATUS_VERIFY = 2;//待审核

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
        'picture',
        'number',
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
            'status' => 'tinyint',
            'picture' => 'int',
            'number' => 'char'
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
                self::STATUS_ACTIVE,
                self::STATUS_VERIFY
            ],
            '状态的范围不正确',
            self::EXISTS_VALIDATE,
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
            self::STATUS_VERIFY,
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

    protected $_link = [
        'Brand' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Brand',
            'foreign_key' => 'brand_id',
            'mapping_name' => '_brand',
            'mapping_order' => 'sort desc',
            // 定义更多的关联属性
        ],
        'Categorys' => [
            'mapping_type' => self::MANY_TO_MANY,
            'class_name' => 'Category',
            'foreign_key' => 'product_id',
            'relation_foreign_key' => 'category_id',
            'mapping_name' => '_categorys',
            'relation_table' => 'sq_product_category'
        ],
        'Norm' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Norms',
            'foreign_key' => 'norms_id',
            'mapping_name' => '_norm',
        ],
        'Picture' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Picture',
            'foreign_key' => 'picture',
            'mapping_name' => '_picture'
        ]
    ];

    protected function _after_find(&$result, $options = '')
    {
        parent::_after_find($result, $options);
//        $result['_status'] = self::getStatusOptions()[$result['status']];
//        $result['_add_time'] = date(C('DATE_FORMAT'), $result['add_time']);
//        $result['_edit_time'] = date(C('DATE_FORMAT'), $result['edit_time']);
    }

    protected function _after_select(&$result, $options = '')
    {
        parent::_after_select($result, $options);
//        foreach ($result as &$value) {
//            $value['_status'] = self::getStatusOptions()[$value['status']];
//            $value['_add_time'] = date(C('DATE_FORMAT'), $result['add_time']);
//            $value['_edit_time'] = date(C('DATE_FORMAT'), $result['edit_time']);
//        }
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
     * @param int $normsIds 规格ID
     * @param null|int $status 状态，可传null或数字
     * @param null|string $title 商品标题，用于模糊搜索，可传NULL
     * @param int $pageSize 分页大小，默认为10
     * @param bool $getCategorys 是否关联查询分类
     * @param bool $getBrand 是否关联查询品牌
     * @param string|array $fields 要查询的字段
     * @return array
     */
    public static function getLists($categoryIds = null, $brandId = null, $normsIds = null, $status = self::STATUS_ACTIVE, $title = null, $pageSize = 10, $getCategorys = false, $getBrand = false, $fields = '*')
    {
        $where = [];
        if ($status === null || !array_key_exists($status, self::getStatusOptions())) {//如果状态为空或者不在系统定义的状态里
            $status = self::STATUS_ACTIVE;
        }
        $bind = [
            ':status' => $status
        ];
        switch (gettype($fields)) {//判断字段类型
            case 'string':
                $fields = trim($fields);
                if ($fields === '*') {
                    $fields = 'p.*';
                } else {
                    $fields = 'p.' . implode(',p.', array_unique(explode(',', $fields)));
                }
                break;
            case 'array':
                $fields = 'p.' . implode(',p.', array_unique($fields));
                break;
            default:
                $fields = 'p.*';
                break;
        }
        if ($getBrand) {//如果需要获取品牌信息
            $fields .= ',b.title _brand_name,b.logo _brand_logo';//增加读取品牌名称和品牌logo
            $getBrand = ' LEFT JOIN ' . BrandModel::getInstance()->getTableName() . ' b ON p.brand_id=b.id';
        } else {
            $getBrand = '';
        }
        //查询的SQL
        $sql = 'SELECT ' . $fields . ' FROM ' . self::getInstance()->getTableName() . ' p' . $getBrand . ' WHERE p.status = :status';
        //统计的SQL
        $totalSql = 'SELECT COUNT(p.id) total FROM ' . self::getInstance()->getTableName() . ' p WHERE p.status = :status';
        if (!empty($brandId)) {//如果品牌ID不会空，则根据品牌ID查询
            $brandId = is_array($brandId) ? implode(',', $brandId) : $brandId;
            $bind[':brandId'] = trim($brandId);//ID去重
            $sql .= ' and brand_id IN (:brandId)';
        }
        if (!empty($title)) {//如果传入了标题，就对标题进行模糊查询
            $bind[':title'] = '%' . $title . '%';
            $sql .= ' and title like :title';
        }
        if (!empty($categoryIds)) {//如果要根据分类ID来查询
            $categoryIds = array_unique(is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds));//ID去重
            $categorySql = ' and p.id IN(SELECT product_id FROM ' . C('DB_PREFIX') . 'product_category WHERE category_id IN (';
            foreach ($categoryIds as $id) {
                $categorySql .= intval($id) . ',';
            }
            $categorySql = rtrim($categorySql, ',') . '))';//去除最右那个多余的,
            //查询数据的SQL
            $sql .= $categorySql;
            //统计数据的SQL
            $totalSql .= $categorySql;
        }
        if (!empty($normsIds)) {//如果规则ID不会空
            $normsIds = array_unique(is_array($normsIds) ? $normsIds : explode(',', $normsIds));//ID去重
            $normsSql = ' AND p.norms_id IN (';
            foreach ($normsIds as $id) {//组合数据
                $normsSql .= intval($id) . ',';
            }
            $normsSql = rtrim($normsSql, ',') . ')';//去除最右那个多余的,
            $sql .= $normsSql;
            $totalSql .= $normsSql;
        }
        $sql .= ' limit ' . ($_GET['p'] ? $_GET['p'] - 1 : 0) * $pageSize . ',' . $pageSize;//对列表进行分页
        $pdo = new \PDO(C('DB_TYPE') . ':host=' . C('DB_HOST') . ';dbname=' . C('DB_NAME'), C('DB_USER'), C('DB_PWD'));
        $pdo->exec('SET NAMES ' . C('DB_CHARSET'));//设置编码字符集
        $sth = $pdo->prepare($sql);
        $sth->execute($bind);
        $totalSth = $pdo->prepare($totalSql);
        $totalSth->execute($bind);
        $total = $totalSth->fetch(\PDO::FETCH_ASSOC);
        $lists = $sth->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($lists as &$item) {
            if (!empty($item['add_ip'])) $item['add_ip'] = long2ip($item['add_ip']);
            if (!empty($item['edit_ip'])) $item['edit_ip'] = long2ip($item['edit_ip']);
            if ($getBrand) {//如果获取了品牌，则把品牌信息压入下级数组，并删除当前级的数据
                $item['_brand'] = [
                    'name' => $item['_brand_name'],
                    'logo' => $item['_brand_logo']
                ];
                unset($item['_brand_name'], $item['_brand_logo']);
            }
            if ($getCategorys) {//如果需要获取分类信息，则独立发送一条SQL查询（由于参数来自数据库查询的结果，所以不用做参数绑定）
                $categorys = $pdo->query('select id,title,list_row,description,icon,level from sq_category WHERE id IN (SELECT category_id FROM sq_product_category WHERE product_id =' . $item['id'] . ') AND status=' . CategoryModel::STATUS_ACTIVE);
                $item['_categorys'] = $categorys->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        return [
            'total' => $total ? (int)$total['total'] : 0,//总数统计
            'data' => $lists,//当前页码的数据
        ];
    }

    /**
     * 根据获取单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id ID
     * @param string|array $fields 要查询的字段
     * @param bool $getCategory 是否关联获取分类信息
     * @param bool $getBrand 是否关联获取品牌信息
     * @return null|array
     */
    public static function get($id, $fields = '*', $getCategory = false, $getBrand = false)
    {
        $id = intval($id);
        $relation = [];
        if ($getCategory) $relation[] = '_categorys';
        if ($getBrand) $relation[] = '_brand';
        return $id ? self::getInstance()->relation($relation)->where(['status' => ['in', [self::STATUS_ACTIVE, self::STATUS_VERIFY]]
            , 'id' => $id])->field($fields)->find() : null;
    }

    /**
     * 根据商品条形码查询商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int|string $number 商品条形码
     * @param string|array $fields 要查询的字段
     * @param bool $getCategorys 是否需要关联查询分类
     * @param bool $getBrand 是否需要关联查询品牌
     * @param bool $getNorm 是否需要关联查询规格
     * @return array|mixed
     */
    public function getByNumber($number, $fields = '*', $getCategorys = false, $getBrand = false, $getNorm = false)
    {
        $number = is_string($number) ? trim($number) : $number;
        if (empty($number)) return [];
        $relation = [
            '_picture'
        ];
        if ($getBrand) $relation[] = '_brand';//关联查询品牌
        if ($getCategorys) $relation[] = '_categorys';
        if ($getNorm) $relation[] = '_norm';
        return self::getInstance()->relation($relation)->field($fields)->where(['number' => $number, 'status' => self::STATUS_ACTIVE])->find();
    }

    /**
     * 根据ID查询商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string|array $ids 多个商品ID
     * @param bool $fields 要查询的字段，默认为所有
     * @param bool $getBrand 是否获得品牌信息
     * @param bool $getCategory 是否关联获取分类信息
     * @return mixed
     */
    public static function getListsByProductIds($ids, $fields = true, $getBrand = false, $getCategory = false)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $ids = array_unique($ids);
        $where['id'] = ['IN', $ids];
        $where['status'] = self::STATUS_ACTIVE;
        $relation = [];
        if ($getBrand) $relation[] = '_brand';
        if ($getCategory) $relation[] = '_categorys';
        return self::getInstance()->relation($relation)->field($fields)->where($where)->select();
    }
}
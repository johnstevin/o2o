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
        ]
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
        ]
    ];

    /**
     * 验证分类是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string|array $categoryIds
     * @return bool
     */
    protected function checkCateExist($categoryIds)
    {
        $ids = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
        $ids = array_unique($ids);
        return true;
    }

    /**
     * 活取所有状态的数组
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
     * @return array
     */
    public static function getLists($categoryIds = null, $brandId = null, $status = self::STATUS_ACTIVE, $title = null, $pageSize = 10)
    {
        $where = [];
        if (!empty($categoryIds)) {
            $categoryIds = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
            $categoryIds = array_unique($categoryIds);
            $categoryProducts = M('product_category')->where(['category_id' => ['IN', $categoryIds]])->select();
            $products = array_map(function ($value) {
                return $value['product_id'];
            }, $categoryProducts);
        }
        if (!empty($brandId)) {
            $brandId = is_array($brandId) ? $brandId : explode(',', $brandId);
            $where['brands'] = ['IN', array_unique($brandId)];
        }
        if (!empty($status)) $where['status'] = in_array($status, array_keys(self::getStatusOptions())) ? $status : self::STATUS_ACTIVE;
        if (!empty($title)) $where['title'] = ['LIKE', trim($title)];
        $model = new self;
        if (isset($products)) {
            $subSql = $model->where(['id' => ['IN', $products]])->buildSql();
            $total = $model->table($subSql . ' sub')->where($where)->count('id');
            $pagination = new Page($total, $pageSize);
            $data = $model->table($subSql . ' sub')->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        } else {
            $total = $model->where($where)->count('id');
            $pagination = new Page($total, $pageSize);
            $data = $model->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        }
        return [
            'data' => $data,
            'pagination' => $pagination->show()
        ];
    }

    /**
     * 根据获取单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id ID
     * @return null|array
     * @throws Exception
     */
    public static function get($id)
    {
        $id = intval($id);
        if ($id === 0) throw new Exception('参数非法', 500);
        $model = new self;
        $where['id'] = $id;
        $where['status'] = self::STATUS_ACTIVE;
        return $model->where($where)->find();
    }
}
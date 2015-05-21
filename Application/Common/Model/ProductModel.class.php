<?php
namespace Common\Model;

use Think\Model\AdvModel;

/**
 * 系统商品模型
 * @author Fufeng Nie <niefufeng@gmail>
 * @package Common\Model
 */
class ProductModel extends AdvModel
{
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭
    protected $fields = [
        'id',
        'title',
        'cate_id',
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

    protected $_validate = [
        [
            'title',
            'require',
            '标题不能为空',
            self::MUST_VALIDATE
        ],
        [
            'cate_id',
            'require',
            '分类不能为空',
            self::MUST_VALIDATE
        ],
        [
            'cate_id',
            'checkCateExist',
            '有非法分类ID',
            self::MUST_VALIDATE,
            'callback'
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
}
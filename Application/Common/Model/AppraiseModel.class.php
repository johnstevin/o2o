<?php
namespace Common\Model;

use Think\Model\RelationModel;

/**
 * 购物评价表
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class AppraiseModel extends RelationModel
{
    protected static $model;

    /**
     * 获取本模型实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return AppraiseModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    protected $fields = [
        'id',
        'order_id',
        'shop_id',
        'user_id',
        'merchant_id',
        'content',
        'grade_1',
        'grade_2',
        'grade_3',
        'status',
        '_type' => [
            'id' => 'int',
            'order_id' => 'int',
            'shop_id' => 'int',
            'user_id' => 'int',
            'merchant_id' => 'int',
            'content' => 'varchar',
            'grade_1' => 'tinyint',
            'grade_2' => 'tinyint',
            'grade_3' => 'tinyint',
            'status' => 'tinyint'
        ]
    ];

    /**
     * 验证规则
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $_validate = [
        [
            'order_id',
            'check_order_exist',
            '订单ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'shop_id',
            'check_merchant_shop_exist',
            '商铺ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'user_id',
            'check_user_exist',
            '用户ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'merchant_id',
            'check_merchant_exist',
            'merchantId非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'grade_1',
            [
                1,
                5
            ],
            '评分非法',
            self::EXISTS_VALIDATE,
            'between'
        ],
        [
            'grade_2',
            [
                1,
                5
            ],
            '评分非法',
            self::EXISTS_VALIDATE,
            'between'
        ],
        [
            'grade_3',
            [
                1,
                5
            ],
            '评分非法',
            self::EXISTS_VALIDATE,
            'between'
        ]
    ];
}
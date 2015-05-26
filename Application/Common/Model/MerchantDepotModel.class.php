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
}
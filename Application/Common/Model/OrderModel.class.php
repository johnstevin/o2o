<?php
namespace Common\Model;

use Think\Model\AdvModel;

class OrderModel extends AdvModel
{
    protected static $model;

    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_CANCEL = 0;//取消的订单
    const STATUS_ACTIVE = 1;//正常
    const STATUS_DELIVERY = 2;//正在配送
    const STATUS_COMPLETE = 3;//已经完成
    ## 支付模式
    const PAY_MODE_ONLINE = 0;//在线支付
    const PAY_MODE_OFFLINE = 1;//线下支付
    ## 支付状态
    const PAY_STATUS_TRUE = 1;//以支付
    const PAY_STATUS_FALSE = 0;//未支付
    ## 配送模式
    const DELIVERY_MODE_PICKEDUP = 0;//自提
    const DELIVERY_MODE_DELIVERY = 1;//配送

    protected $fields = [
        'id',
        'pid',
        'name',
        'price',
        'phone',
        'remark',
        'status',
        'user_id',
        'pay_mode',
        'pay_status',
        'address',
        'add_ip',
        'add_time',
        'update_ip',
        'update_time',
        'deliveryman',
        'delivery_mode',
        'delivery_time',
        '_type' => [
            'id' => 'int',
            'pid' => 'int',
            'name' => 'char',
            'price' => 'double',
            'phone' => 'varchar',
            'remark' => 'varchar',
            'status' => 'tinyint',
            'user_id' => 'int',
            'pay_mode' => 'tinyint',
            'pay_status' => 'tinyint',
            'address' => 'varchar',
            'add_ip' => 'bigint',
            'add_time' => 'int',
            'update_ip' => 'bigint',
            'update_time' => 'int',
            'deliveryman' => 'char',
            'delivery_mode' => 'tinyint',
            'delivery_time' => 'int'
        ]
    ];

    protected $readonlyField = [
        'id',
        'add_time',
        'add_ip',
        'user_id'
    ];

    protected $_validate = [
        [
            'price',
            'currency',
            '价格格式错误'
        ],
        [
            'pay_mode',
            [
                self::PAY_MODE_OFFLINE,
                self::PAY_MODE_ONLINE
            ],
            '支付模式非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'delivery_mode',
            [
                self::DELIVERY_MODE_PICKEDUP,
                self::DELIVERY_MODE_DELIVERY
            ],
            '配送方式非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'delivery_time',
            'number'
        ],
        [
            'status',
            [
                self::STATUS_DELETE,
                self::STATUS_CANCEL,
                self::STATUS_ACTIVE,
                self::STATUS_DELIVERY,
                self::STATUS_COMPLETE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'pid',
            'check_order_exist',
            '父级非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'pay_status',
            [
                self::PAY_STATUS_FALSE,
                self::PAY_STATUS_TRUE
            ],
            '支付状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'add_ip',
            'checkIpFormat',
            'IP格式非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'update_ip',
            'checkIpFormat',
            'IP格式非法',
            self::EXISTS_VALIDATE,
            'function'
        ]
    ];

    protected $_auto = [
        [
            'status',
            self::STATUS_ACTIVE,
            self::MODEL_INSERT
        ],
        [
            'pay_status',
            self::PAY_STATUS_FALSE,
            self::MODEL_INSERT
        ],
        [
            'pay_mode',
            self::PAY_MODE_OFFLINE,
            self::PAY_MODE_OFFLINE
        ],
        [
            'delivery_mode',
            self::DELIVERY_MODE_DELIVERY,
            self::MODEL_INSERT
        ],
        [
            'add_time',
            'time',
            self::MODEL_INSERT,
            'function'
        ],
        [
            'add_ip',
            'get_client_ip',
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
            'update_ip',
            'get_client_ip',
            self::MODEL_UPDATE,
            'function'
        ],
        [
            'pid',
            '',
            self::MODEL_INSERT
        ]
    ];

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return CategoryModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 检测订单是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $id
     * @return bool
     */
    public static function checkOrderExist($id)
    {
        $id = trim($id);
        return ($id !== '' && self::get($id, 'id')) ? true : false;

    }

    /**
     * 获取所有分类的状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '逻辑删除',
            self::STATUS_CANCEL => '取消',
            self::STATUS_ACTIVE => '正常',
            self::STATUS_DELIVERY => '配送中',
            self::STATUS_COMPLETE => '已完成'
        ];
    }

    /**
     * 获得所有配送模式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getDeliveryModeOptions()
    {
        return [
            self::DELIVERY_MODE_DELIVERY => '配送',
            self::DELIVERY_MODE_PICKEDUP => '自提'
        ];
    }

    /**
     * 获取支付的所有模式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getPayModeOptions()
    {
        return [
            self::PAY_MODE_ONLINE => '在线支付',
            self::PAY_MODE_OFFLINE => '线下支付'
        ];
    }

    /**
     * 获取是否需要检查的选项
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getCheckOptions()
    {
        return [
            self::CHECK_DISABLE => '关闭',
            self::CHECK_ENABLE => '开启'
        ];
    }

    /**
     * 根据ID获取分类信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $id 分类ID
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $fields = '*')
    {
        $id = trim($id);
        //TODO 考虑子订单的情况
        return $id ? self::getInstance()->field($fields)->where(['id' => $id, 'status' => ['neq', self::STATUS_DELETE]])->find() : null;
    }
}
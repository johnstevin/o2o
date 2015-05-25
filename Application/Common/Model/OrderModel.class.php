<?php
namespace Common\Model;

use Think\Model\RelationModel;
use Think\Page;

/**
 * Class OrderModel
 * @property int $id
 * @property int $pid 父级ID
 * @property string $consignee 收货人姓名
 * @property float $price 订单价格
 * @property string $mobile 收货人联系电话
 * @property string $remark 订单备注
 * @property int $status 订单状态
 * @property int $user_id 用户ID
 * @property int $pay_status 订单支付状态
 * @property int $pay_mode 支付方式
 * @property string $address 收货地址
 * @property int $add_id 创建订单的IP
 * @property int $add_time 创建订单的时间
 * @property int $update_ip 更新订单的IP
 * @property int $update_time 更新订单的时间
 * @property string $deliveryman 送货员
 * @property int $delivery_mode 送货模式
 * @property int $delivery_time 送货时间
 *
 * @package Common\Model
 */
class OrderModel extends RelationModel
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

    protected $_link = [
        'Childs' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'Order',
            'parent_key' => 'pid',
            'mapping_name' => '_childs',
            'mapping_order' => 'id desc',
            // 定义更多的关联属性
        ],
        'Products' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'OrderItem',
            'foreign_key' => 'order_id',
            'mapping_name' => '_products',
        ]
    ];

    protected $fields = [
        'id',
        'pid',
        'price',
        'mobile',
        'remark',
        'status',
        'user_id',
        'pay_mode',
        'consignee',
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
            'price' => 'double',
            'mobile' => 'varchar',
            'remark' => 'varchar',
            'status' => 'tinyint',
            'user_id' => 'int',
            'consignee' => 'char',
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
        'user_id',
        'order_code'
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
        ],
        [
            'order_code',
            'unique',
            '订单代码已经存在',
            self::EXISTS_VALIDATE
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
        ],
        [
            'order_code',
            'create_order_code',
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
     * 获取所有的支付状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getPayStatusOptions()
    {
        return [
            self::PAY_STATUS_TRUE,
            self::PAY_STATUS_FALSE
        ];
    }

    /**
     * 根据ID获取分类信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $id 分类ID
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $status = null, $fields = '*')
    {
        $id = trim($id);
        if (empty($id)) return null;
        $where['id'] = $id;
        if (!empty($status) && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = ['NEQ', self::STATUS_DELETE];
        }
        if (!$order = self::getInstance()->field($fields)->where($where)->find()) {
            return [];
        }
        if ($subOrder = self::getLists($order['shop_id'], $order['user_id'], $order['status'], null, $fields, $order['id'], false)) {

        }
    }

    /**
     * 根据用户ID获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param bool|array|string $fields 要查询的字段
     * @param bool $relation 是否进行关联查询
     * @return array|null
     */
    public static function getListsByUserId($userId, $status = null, $payStatus = null, $fields = true, $relation = true)
    {
        return self::getLists(null, $userId, $status, $payStatus, $fields, $relation);
    }

    /**
     * 根据商铺ID获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $shopId 商铺ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param bool|array|string $fields 要查询的字段
     * @param bool $relation 是否进行关联查询
     * @return array|null
     */
    public static function getListsByShopId($shopId, $status = null, $payStatus = null, $fields = true, $relation = true)
    {
        return self::getLists($shopId, null, $status, $payStatus, $fields, $relation);
    }

    /**
     * 获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $shopId 商铺ID
     * @param null|int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param bool|array|string $fields 要查询的字段，按照TP模型规则来，如果自定义，必须要包括pid字段
     * @param bool $relation 是否进行关联查询
     * @return array|null
     */
    public static function getLists($shopId = null, $userId = null, $status = null, $payStatus = null, $fields = true, $relation = true)
    {
        $where = [
            'pid' => 0
        ];
        if (!empty($shopId)) $where['shop_id'] = intval($shopId);
        if (!empty($userId)) $where['user_id'] = intval($userId);
        if (!empty($status) && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = ['NEQ', self::STATUS_DELETE];
        }
        if (!empty($payStatus) && in_array($payStatus, array_keys(self::getPayStatusOptions()))) $where['pay_status'] = $payStatus;
        $model = self::getInstance();
        $total = $model->where($where)->count('id');
        $pagination = new Page($total);
        $data = $model->relation($relation)->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->field($fields)->select();
        foreach ($data as &$value) {
            if (!empty($value['_childs'])) {
                foreach ($value['_childs'] as &$child) {
                    $child['_products'] = self::getProducts($value['id']);
                }
            }
        }
        return $data;
    }

    public static function getProducts($orderId)
    {
    }
}
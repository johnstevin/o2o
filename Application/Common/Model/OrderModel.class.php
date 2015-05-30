<?php
namespace Common\Model;

use Think\Model\RelationModel;
use Think\Page;

/**
 * Class OrderModel
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
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
    protected $autoinc = true;
    protected $patchValidate = true;

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

    /**
     * 关联
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $_link = [
        'Childs' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'Order',
            'parent_key' => 'pid',
            'mapping_name' => '_childs',
            'mapping_order' => 'id',
            'condition' => 'status !=' . self::STATUS_DELETE
            // 定义更多的关联属性
        ],
        'Products' => [
            'mapping_type' => self::HAS_MANY,
            'class_name' => 'OrderItem',
            'foreign_key' => 'order_id',
            'mapping_name' => '_products',
        ],
        'Parent' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Order',
            'parent_key' => 'pid',
            'mapping_name' => '_parent',
            'condition' => 'status !=' . self::STATUS_DELETE
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
        'order_code',
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
            'order_code' => 'varchar',
            'pay_status' => 'tinyint',
            'address' => 'varchar',
            'add_ip' => 'char',
            'add_time' => 'int',
            'update_ip' => 'char',
            'update_time' => 'int',
            'deliveryman' => 'char',
            'delivery_mode' => 'tinyint',
            'delivery_time' => 'int'
        ]
    ];

    protected $readonlyField = [
        'id',
        'pid',
        'shop_id',
        'add_time',
        'add_ip',
        'user_id',
        'order_code'
    ];

    /**
     * 验证规则
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
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
            'checkOrderPidExist',
            '父级ID非法',
            self::EXISTS_VALIDATE,
            'callback'
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

    /**
     * 自动完成
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
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
            'order_code',
            'create_order_code',
            self::MODEL_INSERT,
            'function'
        ]
    ];

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return OrderModel
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
        $id = intval($id);
        return ($id !== '' && self::get($id, 'id')) ? true : false;
    }

    /**
     * 检测父级ID是否合法
     * @param int $id
     * @return bool
     */
    public static function checkOrderPidExist($id)
    {
        $id = intval($id);
        return ($id === 0 || self::checkOrderExist($id)) ? true : false;
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
     * @param int|null $status 订单状态，默认为不等于删除的
     * @param bool $getChilds 是否获得子订单
     * @param bool $getProducts 是否获得订单的商品
     * @param bool $getParent 是否获取父级订单
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $status = null, $fields = '*', $getChilds = true, $getProducts = false, $getParent = false)
    {
        $id = trim($id);
        if (empty($id)) return null;
        $where['id'] = $id;
        if (!empty($status) && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = ['NEQ', self::STATUS_DELETE];
        }
        $relation = [];
        if ((bool)$getChilds) $relation[] = '_childs';
        if ($getProducts) $relation[] = '_products';
        if ($getParent) $relation[] = '_parent';
        $data = self::getInstance()->relation($relation)->field($fields)->where($where)->find();
        if (!$getProducts) return $data;
        if (!empty($data['_products'])) {
            $productIds = array_map(function ($order) {
                return $order['product_id'];
            }, $data['_products']);
            $data['_products'] = ProductModel::getListsByProductIds($productIds);
        } else {
            $orderItemModel = M('order_item');
            foreach ($data['_childs'] as &$child) {
                $productIds = $orderItemModel->field('product_id')->where(['order_id' => $child['id']])->select();
                $productIds = array_map(function ($item) {
                    return $item['product_id'];
                }, $productIds);
                $child['_products'] = ProductModel::getListsByProductIds($productIds);
            }
        }
        return $data;
    }

    /**
     * 根据用户ID获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param bool|array|string $fields 要查询的字段
     * @param bool $getProducts 是否查询订单下的商品列表
     * @return array|null
     */
    public static function getListsByUserId($userId, $status = null, $payStatus = null, $fields = '*', $getProducts = true)
    {
        return self::getLists(null, $userId, $status, $payStatus, $fields, $getProducts);
    }

    /**
     * 根据商铺ID获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $shopId 商铺ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param bool|array|string $fields 要查询的字段
     * @param bool $getProducts 是否查询订单下的商品列表
     * @return array|null
     */
    public static function getListsByShopId($shopId, $status = null, $payStatus = null, $fields = '*', $getProducts = true)
    {
        return self::getLists($shopId, null, $status, $payStatus, $fields, $getProducts);
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
     * @param bool $getProducts 是否查询订单下的商品列表
     * @return array|null
     */
    public static function getLists($shopId = null, $userId = null, $status = null, $payStatus = null, $fields = '*', $getProducts = false)
    {
        $where = [
            'pid' => 0
        ];
        if (!empty($shopId)) $where['shop_id'] = intval($shopId);
        if (!empty($userId)) $where['user_id'] = intval($userId);
        if ($status !== null && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = ['NEQ', self::STATUS_DELETE];
        }
        if (!empty($payStatus) && in_array($payStatus, array_keys(self::getPayStatusOptions()))) $where['pay_status'] = $payStatus;
        $model = self::getInstance();
        $total = $model->where($where)->count('id');
        $pagination = new Page($total);
        $data = $model->relation('_childs')->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->field($fields)->select();
        if ($getProducts) {
            foreach ($data as &$value) {
                if (empty($value['_childs'])) {
                    $value['_products'] = self::getProductsByOrderId($value['id']);
                } else {
                    foreach ($value['_childs'] as &$child) {
                        $child['_products'] = self::getProductsByOrderId($child['id']);
                    }
                }
            }
        }
        return [
            'data' => $data,
            'pagination' => $pagination->show()
        ];
    }

    /**
     * 获取订单的商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $orderId 订单ID
     * @return null|array
     */
    public static function getProductsByOrderId($orderId)
    {
        if (!$order = self::get($orderId, null, 'id', false, true)) return null;
        return $order['_products'];
    }

    /**
     * 提交订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $userId 用户ID
     * @param string|array $cart 购物车，可传json格式或者数组格式
     * @param string $mobile 收货人联系电话
     * @param string $consignee 收货人
     * @param string $address 收货地址
     * @param string $remark 订单备注
     * @param int $payMode 支付方式
     * @param int $deliveryMode 配送方式
     * @param bool $split 是否需要拆单
     * @return bool
     */
    public static function submitOrder($userId, $cart, $mobile, $consignee, $address, $remark, $payMode = self::PAY_MODE_OFFLINE, $deliveryMode = self::DELIVERY_MODE_DELIVERY, $split = true)
    {
        $userId = intval($userId);
        if (!check_user_exist($userId)) E('用户：' . $userId . '不存在');
        $cart = is_array($cart) ?: json_decode($cart, true);
        $products = [];
        if ($split) {
            foreach ($cart as $product) {
                $products[$product['shop_id']][] = $product;
            }
        } else {
            $products = $cart;
        }
        $parentId = 0;
        //判断购物车格式，如果二级数组的值还是数组，那么就是拆单的
        if (is_array(current(current($products)))) {
            $allPrice = [];
            foreach ($products as $key => $product) {//取出所有价格字段
                $allPrice[$key] = array_column($product, 'price');
            }
            $priceTotal = 0;
            foreach ($allPrice as $price) {//循环累加获得总价
                $priceTotal += array_sum($price);
            }
            $parentId = self::createEmptyParentOrder($userId, 0, $priceTotal, $payMode, $deliveryMode);
            //TODO 这个要改为在当前方法做，方便使用事务
            foreach ($products as $key => $item) {
                self::createOrder($userId, $key, $item, $mobile, $consignee, $address, $parentId, $remark, $payMode, $deliveryMode);
            }
            return true;
        } else {
            return self::createOrder($userId, current($products)['shop_id'], $products, $mobile, $consignee, $address, $parentId, $remark, $payMode, $deliveryMode);
        }
    }

    /**
     * 创建一个空的父级订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $userId 用户ID
     * @param int $shopId 商铺ID
     * @param int $price 价格
     * @param int $payMode 支付模式
     * @param int $deliveryMode 配送模式
     * @return int
     */
    public static function createEmptyParentOrder($userId, $shopId = 0, $price = 0, $payMode = self::PAY_MODE_OFFLINE, $deliveryMode = self::DELIVERY_MODE_DELIVERY)
    {
        $data['price'] = 0;
        $data['pid'] = 0;
        $data['remark'] = '';
        $data['user_id'] = $userId;
        $data['shop_id'] = $shopId;
        $data['price'] = $price;
        $data['pay_mode'] = $payMode;
        $data['delivery_mode'] = $deliveryMode;
        $model = self::getInstance();
        if (!$model->create($data) || !$model->add()) E($model->getError());
        return $model->getLastInsID();
    }

    /**
     * 创建订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $userId 用户ID
     * @param int $shopId 商铺ID
     * @param array $products 订单的商品
     * @param int|string $mobile 收货人联系方式
     * @param string $consignee 收货人姓名
     * @param string $address 收货人地址
     * @param int $pid 父级ID
     * @param string $remark 订单备注
     * @param int $payMode 支付方式
     * @param int $deliveryMode 配送方式
     * @return bool
     */
    public static function createOrder($userId, $shopId, $products, $mobile, $consignee, $address, $pid, $remark, $payMode = self::PAY_MODE_OFFLINE, $deliveryMode = self::DELIVERY_MODE_DELIVERY)
    {
        $data['user_id'] = $userId;
        $data['pid'] = $pid;
        $data['shop_id'] = $shopId;
        $data['pay_mode'] = $payMode;
        $data['delivery_mode'] = $deliveryMode;
        $data['mobile'] = $mobile;
        $data['consignee'] = $consignee;
        $data['address'] = $address;
        $data['remark'] = $remark;
        $allPrice = array_column($products, 'price');
        $data['price'] = array_sum($allPrice);
        $data['_products'] = $products;
        $model = self::getInstance();
        if (!$model->relation(true)->create($data)) E($model->getError());
        return $orderId = $model->relation(true)->add();
    }

    /**
     * 更新订单信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param null|int $payMode 支付方式
     * @param null|int $deliveryMode 配送方式
     * @param null|int $deliveryTime 配送时间
     * @param null|string|int $mobile 收货人联系方式
     * @param null|string $address 收货地址
     * @param null|string $consignee 收货人
     * @return bool
     */
    public static function updateOrder($id, $payMode = null, $deliveryMode = null, $deliveryTime = null, $mobile = null, $address = null, $consignee = null)
    {
        $data = [];
        if ($payMode !== null) $data['pay_mode'] = $payMode;
        if ($deliveryMode !== null) $data['delivery_mode'] = $deliveryMode;
        if (!empty($deliveryTime)) $data['delivery_time'] = $deliveryTime;
        if (!empty($mobile)) $data['mobile'] = $mobile;
        if (!empty($address)) $data['address'] = trim($address);
        if (!empty($consignee)) $data['consignee'] = trim($consignee);
        return self::getInstance()->where(['id' => intval($id)])->save($data);
    }

    /**
     * 更新订单状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $status 订单状态
     * @return bool
     */
    public static function updateOrderStatus($id, $status)
    {
        return self::getInstance()->where(['id' => intval($id), '_logic' => 'OR', 'pid' => intval($id)])->save(['status' => intval($status)]);
    }

    /**
     * 更新订单支付方式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payMode 支付方式
     * @return bool
     */
    public static function updateOrderPayMode($id, $payMode)
    {
        return self::getInstance()->where(['id' => intval($id), '_logic' => 'OR', 'pid' => intval($id)])->save(['pay_mode' => intval($payMode)]);
    }

    /**
     * 更新订单的支付方式
     * @param int $id 订单ID
     * @param int $payStatus 支付状态
     * @return bool
     */
    public static function updateOrderPayStatus($id, $payStatus)
    {
        return self::getInstance()->where(['id' => intval($id), '_logic' => 'OR', 'pid' => intval($id)])->save(['pay_status' => intval($payStatus)]);
    }
}
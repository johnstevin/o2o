<?php
namespace Common\Model;

use Think\Exception;
use Think\Log;
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
 * @property int $delivery_price 配送费
 *
 * @package Common\Model
 */
class OrderModel extends RelationModel
{
    /**
     * @var self
     */
    protected static $model;
    protected $autoinc = true;
    protected $pk = 'id';
    protected $patchValidate = true;

    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_CANCEL = 0;//取消的订单
    const STATUS_MERCHANT_CONFIRM = 1;//等待商家确定
    const STATUS_USER_CONFIRM = 2;//用户确定（商家的修改）
    const STATUS_DELIVERY = 3;//正在配送
    const STATUS_COMPLETE = 4;//已经完成
    const STATUS_REFUND = 5;//申请退款
    const STATUS_REFUND_COMPLETE = 6;//退款完成

    ## 支付模式
    const PAY_MODE_OFFLINE = 0;//线下支付
    const PAY_MODE_WECHAT = 1;//微信支付
    const PAY_MODE_ALIPAY = 2;//支付宝支付
    const PAY_MODE_ONLINE_BANK = 3;//网银

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
//            'condition' => 'status !=' . self::STATUS_DELETE
            'condition' => 'status != -1'
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
//            'condition' => 'status !=' . self::STATUS_DELETE
            'condition' => 'status != -1'
        ],
        'UcenterMember' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'UcenterMember',
            'foreign_key' => 'user_id',
            'mapping_name' => '_ucenter_member',
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
        'shop_id',
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
        'delivery_price',
        '_type' => [
            'id' => 'int',
            'pid' => 'int',
            'price' => 'double',
            'mobile' => 'varchar',
            'remark' => 'varchar',
            'status' => 'tinyint',
            'user_id' => 'int',
            'shop_id' => 'int',
            'consignee' => 'char',
            'pay_mode' => 'tinyint',
            'order_code' => 'char',
            'pay_status' => 'tinyint',
            'address' => 'varchar',
            'add_ip' => 'char',
            'add_time' => 'int',
            'update_ip' => 'char',
            'update_time' => 'int',
            'deliveryman' => 'char',
            'delivery_mode' => 'tinyint',
            'delivery_time' => 'int',
            'delivery_price' => 'float'
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
                self::PAY_MODE_WECHAT,
                self::PAY_MODE_ALIPAY,
                self::PAY_MODE_ONLINE_BANK
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
                self::STATUS_MERCHANT_CONFIRM,
                self::STATUS_USER_CONFIRM,
                self::STATUS_DELIVERY,
                self::STATUS_COMPLETE,
                self::STATUS_REFUND,
                self::STATUS_REFUND_COMPLETE,
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
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
            'order_code',
            'unique',
            '订单代码已经存在',
            self::EXISTS_VALIDATE
        ],
        [
            'user_id',
            'check_user_exist',
            '用户ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'shop_id',
            'check_shop_exist',
            '商铺ID非法',
            self::EXISTS_VALIDATE,
            'function'
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
            self::STATUS_MERCHANT_CONFIRM,
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
        return ($id !== 0 && self::get($id, null, 'id', '*', false)) ? true : false;
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
            self::STATUS_DELETE => '删除',
            self::STATUS_CANCEL => '取消',
            self::STATUS_MERCHANT_CONFIRM => '商家确认',
            self::STATUS_USER_CONFIRM => '用户确认',
            self::STATUS_DELIVERY => '配送中',
            self::STATUS_COMPLETE => '已完成',
            self::STATUS_REFUND => '申请退款',
            self::STATUS_REFUND_COMPLETE => '退款完成',
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
            self::PAY_MODE_OFFLINE => '线下支付',
            self::PAY_MODE_WECHAT => '微信支付',
            self::PAY_MODE_ALIPAY => '支付宝支付',
            self::PAY_MODE_ONLINE_BANK => '网银支付'
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
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $status = null, $fields = '*', $getChilds = true, $getProducts = false)
    {
        $id = intval($id);
        if ($id === 0) return [];
        $where['id'] = $id;
        if ($status !== null && array_key_exists($status, self::getStatusOptions())) {
            $where['status'] = $status;
        } else {
            $where['status'] = ['NEQ', self::STATUS_DELETE];
        }
        $relation = [];
        if ((bool)$getChilds) $relation[] = '_childs';
        $data = self::getInstance()->relation($relation)->field($fields)->where($where)->find();
        if (!$getProducts) return $data;
        $data['_products'] = [];
        $pdo = get_pdo();
        if (empty($data['_childs'])) {
            $sth = $pdo->prepare('SELECT md.id depot_id,p.id product_id,product_picture.path picture_path,p.title,p.number,p.norms_id,md.price,md.remark,p.detail,p.brand_id FROM sq_merchant_depot md LEFT JOIN sq_product p ON md.product_id=p.id LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id WHERE md.id IN (select product_id from sq_order_item WHERE order_id=:order_id) AND p.status=:product_status');
            $sth->execute([':order_id' => $id, ':product_status' => ProductModel::STATUS_ACTIVE]);
            $data['_products'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            foreach ($data['_childs'] as &$child) {
                $sth = $pdo->prepare('SELECT md.id depot_id,p.id product_id,product_picture.path picture_path,p.title,p.number,p.norms_id,md.price,md.remark,p.detail,p.brand_id FROM sq_merchant_depot md LEFT JOIN sq_product p ON md.product_id=p.id LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id WHERE md.id IN (select product_id from sq_order_item WHERE order_id=:order_id) AND p.status=:product_status');
                $sth->execute([':order_id' => $id, ':product_status' => ProductModel::STATUS_ACTIVE]);
                $child['_products'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        return $data;
    }

    /**
     * 根据订单号获取订单信息（不是订单ID哈）
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $code 订单号
     * @param null|int $status 订单状态
     * @param bool|false $getChilds 是否获得子订单
     * @param bool|false $getProducts 是否获得订单商品
     * @param bool|false $getShop 是否获取订单所属商家
     * @param string $fields
     * @return array|null
     */
    public function getByCode($code, $status = null, $getChilds = false, $getProducts = false, $getShop = false, $fields = '*')
    {
        $code = trim($code);
        if ($code === '') E('code非法');
        $where = [
            'order_code' => $code
        ];
        $relation = [];
        $status = intval($status);
        if ($status !== null && array_key_exists($status, self::getStatusOptions())) {
            $where['status'] = $status;
        } else {
            $where['status'] = [
                'NEQ',
                self::STATUS_DELETE
            ];
        }
        if ($getChilds) $relation[] = '_childs';
        $data = self::getInstance()->relation($relation)->where($where)->field($fields)->select();
        if ($getProducts || $getShop) {
            $pdo = get_pdo();
            if (empty($data['_childs'])) {
                if ($getProducts) {
                    $sth = $pdo->prepare('SELECT oi.depot_id,oi.price,oi.total,p.id product_id,product_picture.path picture,p.title,p.detail,p.brand_id,p.norms_id,p.number FROM sq_order_item oi LEFT JOIN sq_merchant_depot md ON oi.depot_id=md.id LEFT JOIN sq_product p ON md.product_id=p.id LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id  WHERE oi.order_id=:order_id AND p.status=' . ProductModel::STATUS_ACTIVE);
                    $sth->execute([':order_id' => $data['id']]);
                    $data['_products'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
                }
                if ($getShop) {
                    $sth = $pdo->prepare('SELECT ms.id,p.path picture,ms.address,ms.phone_number,ms.type,ms.title FROM sq_merchant_shop ms LEFT JOIN sq_picture p ON ms.picture=p.id WHERE ms.id=:shop_id');
                    $sth->execute([':shop_id' => $data['shop_id']]);
                    $data['_shop'] = $sth->fetch(\PDO::FETCH_ASSOC);
                }
            } else {
                foreach ($data['_childs'] as &$item) {
                    if ($getProducts) {
                        $sth = $pdo->prepare('SELECT oi.depot_id,oi.price,oi.total,p.id product_id,product_picture.path picture,p.title,p.detail,p.brand_id,p.norms_id,p.number FROM sq_order_item oi LEFT JOIN sq_merchant_depot md ON oi.depot_id=md.id LEFT JOIN sq_product p ON md.product_id=p.id LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id  WHERE oi.order_id=:order_id AND p.status=' . ProductModel::STATUS_ACTIVE);
                        $sth->execute([':order_id' => $item['id']]);
                        $item['_products'] = $sth->fetchAll(\PDO::FETCH_ASSOC);
                    }
                    if ($getShop) {
                        $sth = $pdo->prepare('SELECT ms.id,p.path picture,ms.address,ms.phone_number,ms.type,ms.title FROM sq_merchant_shop ms LEFT JOIN sq_picture p ON ms.picture=p.id WHERE ms.id=:shop_id');
                        $sth->execute([':shop_id' => $item['shop_id']]);
                        $item['_shop'] = $sth->fetch(\PDO::FETCH_ASSOC);
                    }
                }
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
     * @param null|int $deliveryMode 配送方式
     * @param bool $getShop 是否获得店铺信息
     * @param bool $getUser 是否获得用户信息
     * @param bool $getProducts 是否查询订单下的商品列表
     * @return array|null
     */
    public static function getListsByUserId($userId, $status = null, $payStatus = null, $deliveryMode = null, $getShop = false, $getUser = false, $getProducts = true)
    {
        return self::getInstance()->getLists(null, $userId, $status, $payStatus, $deliveryMode, $getShop, $getUser, $getProducts);
    }

    /**
     * 根据商铺ID获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $shopId 商铺ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param null|int $deliveryMode 配送方式
     * @param bool $getShop 是否获得店铺信息
     * @param bool $getUser 是否获得用户信息
     * @param bool $getProducts 是否查询订单下的商品列表
     * @return array|null
     */
    public static function getListsByShopId($shopId, $status = null, $payStatus = null, $deliveryMode = null, $getShop = false, $getUser = false, $getProducts = true)
    {
        return self::getInstance()->getLists($shopId, null, $status, $payStatus, $deliveryMode, $getShop, $getUser, $getProducts);
    }

    /**
     * 获取订单列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $shopId 商铺ID
     * @param null|int $userId 用户ID
     * @param null|int $status 订单状态
     * @param null|int $payStatus 支付状态
     * @param null|int $deliveryMode 配送方式
     * @param bool $getShop 是否获得店铺信息
     * @param bool $getUser 是否获得用户信息
     * @param bool $getProducts 是否查询订单下的商品列表
     * @param int $pageSize 分页大小
     * @return array|null
     */
    public function getLists($shopId = null, $userId = null, $status = null, $payStatus = null, $deliveryMode = null, $getShop = false, $getUser = false, $getProducts = false, $pageSize = 10)
    {
        if (empty($shopId) && empty($userId)) E('非法请求');
        if (!empty($shopId)) {
            $where['o.shop_id'] = intval($shopId);
        }
        if (!empty($userId)) {
            $where['o.user_id'] = intval($userId);
            $where['o.pid'] = 0;//如果根据用户来查，需要把父级也查出来
        }
        if (!empty($shopId) && !empty($userId)) {
            $where['o.pid'] = 0;
        }
        if ($status !== null) {
            $status = is_array($status) ?: explode(',', $status);
            $where['o.status'] = [
                'IN',
                $status
            ];
        } else {
            $where['o.status'] = ['NEQ', self::STATUS_DELETE];
        }
        if ($payStatus !== null && in_array($payStatus, array_keys(self::getPayStatusOptions()))) $where['o.pay_status'] = $payStatus;
        if ($deliveryMode !== null) {
            $where['delivery_mode'] = intval($deliveryMode);
        }
        $fields = [
            'o.*'
        ];
        $model = self::getInstance()->alias('o')->where($where);
        $totalModel = clone $model;
        if ($getUser) {
            $fields = array_merge($fields, [
                'm.nickname _user_nickname',
                'm.sex _user_sex',
                'um.email _user_email',
                'user_picture.path _user_photo'
            ]);
            $model->join('LEFT JOIN sq_ucenter_member um ON um.id=o.user_id');
            $model->join('LEFT JOIN sq_member m ON m.uid=um.id');
            $model->join('LEFT JOIN sq_picture user_picture ON um.photo=user_picture.id');
        }
        $total = $totalModel->count();
        $pagination = new Page($total, $pageSize);
        $data = $model->relation('_childs')->field($fields)->limit($pagination->firstRow . ',' . $pagination->listRows)->order('o.add_time desc,o.update_time desc')->select();
        $orderItemModel = M('OrderItem');
        $pdo = get_pdo();
        foreach ($data as &$item) {
            if ($getUser) {
                $item['_user'] = [
                    'id' => $item['user_id'],
                    'nickname' => $item['_user_nickname'],
                    'sex' => $item['_user_sex'],
                    'email' => $item['_user_email'],
                    'photo' => $item['_user_photo']
                ];
                unset($item['user_id'], $item['_user_nickname'], $item['_user_sex'], $item['_user_email'], $item['_user_photo']);
            }
            if ($getProducts || $getShop) {
                if (empty($item['_childs'])) {
                    if ($getProducts) {//获取产品
                        $item['_products'] = $orderItemModel->field([
                            'oi.depot_id',
                            'p.title',
                            'p.id product_id',
                            'md.price',
                            'oi.total',
                            'p.detail',
                            'n.title norm',
                            'product_picture.path picture_path'
                        ])->alias('oi')->where(['oi.order_id' => $item['id']])->join('LEFT JOIN sq_merchant_depot md ON md.id=oi.depot_id')
                            ->join('LEFT JOIN sq_product p ON md.product_id=p.id')->join('LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id')
                            ->join('LEFT JOIN sq_norms n ON n.id=p.norms_id')
                            ->select();
                    }
                    if ($getShop) {//获取商铺信息
                        $sth = $pdo->prepare('SELECT ms.id,ms.title,ms.description,ms.status,ms.type,ms.phone_number phone,ms.address,ms.open_status,ms.region_id,p.path picture FROM sq_merchant_shop ms LEFT JOIN sq_picture p ON ms.picture=p.id WHERE ms.id=:shop_id');
                        $sth->execute([':shop_id' => $item['shop_id']]);
                        $item['_shop'] = $sth->fetch(\PDO::FETCH_ASSOC);
                    }
                } else {
                    $item['_products'] = [];
                    foreach ($item['_childs'] as &$child) {
                        if ($getProducts) {//获取产品信息
                            $child['_products'] = $orderItemModel->field([
                                'oi.depot_id',
                                'p.title',
                                'p.id product_id',
                                'md.price',
                                'oi.total',
                                'p.detail',
                                'n.title norm',
                                'product_picture.path picture_path'
                            ])->alias('oi')->where(['oi.order_id' => $child['id']])->join('LEFT JOIN sq_merchant_depot md ON md.id=oi.depot_id')
                                ->join('LEFT JOIN sq_product p ON md.product_id=p.id')->join('LEFT JOIN sq_picture product_picture ON p.picture=product_picture.id')
                                ->join('LEFT JOIN sq_norms n ON n.id=p.norms_id')
                                ->select();
                        }
                        if ($getShop) {//获取商铺信息
                            $sth = $pdo->prepare('SELECT ms.id,ms.title,ms.description,ms.status,ms.type,ms.phone_number phone,ms.address,ms.open_status,ms.region_id,p.path picture FROM sq_merchant_shop ms LEFT JOIN sq_picture p ON ms.picture=p.id WHERE ms.id=:shop_id');
                            $sth->execute([':shop_id' => $child['shop_id']]);
                            $child['_shop'] = $sth->fetch(\PDO::FETCH_ASSOC);
                        }
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
     * 初始化订单，用于分配未指定商家的商品到商铺，计算价格等，会把计算后的结果缓存到服务器
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param json|array $cart 购物车
     * @param int $deliveryMode 配送模式
     * @param null|int $deliveryTime 配送时间
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param bool $split 是否需要系统进行拆单
     * @return array
     */
    public function initOrder($cart, $deliveryMode = self::DELIVERY_MODE_DELIVERY, $deliveryTime = null, $lng, $lat, $split = true)
    {
        $cart = is_array($cart) ? $cart : json_decode($cart, true);
        $depotModel = MerchantDepotModel::getInstance();
        $shopModel = MerchantShopModel::getInstance();
        if ($split) {//如果需要系统拆单
            $products = [];//已经确定商家的商品
            $shopIds = [];//购物车中涉及到的所有商家，用于计算每个商家距离之后分配商品
            $hasProductShopIds = [];//购物车中已经确定商品归属的商家
            $_cart = [];//需要拆单的商品
            $notAllocationProducts = [];//需要拆单的商品列表
            $notAllocationProductsShopIds = [];//有未分配商品的商家

            $productTotal = array_sum(array_column($cart, 'total'));

            foreach ($cart as $key => $product) {
                $shopIds = array_merge($shopIds, $product['shop_id']);//把商铺ID加入到商铺ID列表里
                //如果这个商品只有这家有卖或者用户选定了这家商家，则直接放入已经确定商家的商品列表里
                if (count($product['shop_id']) === 1) {
                    $hasProductShopIds[] = current($product['shop_id']);
                    $products[$product['depot_id']] = [
                        'total' => $product['total'],
                        'depot_id' => $product['depot_id'],
                        'shop_id' => current($product['shop_id']),
                        'product_id' => $product['product_id']
                    ];
                } else {//否则放入需要系统分配商家的列表里
                    $_cart[] = $product;
                    $notAllocationProducts[$product['product_id']] = [
                        'total' => $product['total'],
                        'product_id' => $product['product_id'],
                    ];
                    $notAllocationProductsShopIds = array_merge($notAllocationProductsShopIds, $product['shop_id']);
                }
            }

            //统计目前已经确定要配货的商家要配送的货物数量【注意：这个有可能为空】
            $hasProductShopTotal = array_count_values($hasProductShopIds);
            arsort($hasProductShopTotal);//排序，方便找出要送货最多的商家
            $shopIds = array_unique($shopIds);
            if (empty($shopIds)) E('没有找到商铺');
            $notAllocationProductsShopIds = array_unique($notAllocationProductsShopIds);
            //如果未分配的商品不为空
            if (!empty($notAllocationProducts)) {
                //如果配送时间不等于空
                //根据未分配商家的商品ID找出所有有这个商品的商家并按商品ID和价格排序
                $_depots = $depotModel->field('id,shop_id,price,product_id')->where([
                    'product_id' => ['IN', array_column($notAllocationProducts, 'product_id')],
                    'shop_id' => ['IN', $notAllocationProductsShopIds],
                    'status' => MerchantDepotModel::STATUS_ACTIVE
                ])->order('product_id,price')->select();
                if (empty($_depots)) E('没有找到商品');
                $depots = [];
                $shopLists = [];//商家统计
                //根据商品ID对仓库的商品进行分组
                foreach ($_depots as $key => &$depot) {
                    $depots[$depot['product_id']][] = &$_depots[$key];
                    $shopLists[] = $depot['shop_id'];
                }
                //TODO 目前是直接往价格最低的商家分配商品，待优化
                foreach ($depots as $depot) {
                    $products[$depot[0]['id']] = [
                        'total' => $notAllocationProducts[$depot[0]['product_id']]['total'],
                        'depot_id' => $depot[0]['id'],
                        'shop_id' => $depot[0]['shop_id'],
                        'product_id' => $depot[0]['product_id']
                    ];
                }
            }
            $order = [];
            foreach ($products as $key => $product) {
                $order[$product['shop_id']][] = &$products[$key];
            }
        } else {
            $order = &$cart;
        }
        $depotIds = [];
        foreach ($order as $item) {
            foreach ($item as $product) {
                $depotIds[] = $product['depot_id'];
            }
        }
        // 查询出所有商品的价格
        $_allDepotInfo = $depotModel->alias('d')->field([
            'd.id',
            'd.price',
            'p.title',
            'p.detail',
            'pp.path picture_path',
            'd.product_id',
            'b.title _brand_title',
            'bp.path _brand_logo',
            'b.id _brand_id',
            'ms.id _shop_id',
            'ms.title _shop_title',
            'ms.type _shop_type',
            'ms.address _shop_address',
            'ms.phone_number _shop_phone',
        ])->where([
            'd.status' => MerchantDepotModel::STATUS_ACTIVE,
            'd.id' => [
                'IN',
                $depotIds
            ]
        ])->join('LEFT JOIN sq_product p ON p.id=d.product_id')
            ->join('LEFT JOIN sq_picture pp ON p.picture=pp.id')
            ->join('LEFT JOIN sq_brand b ON p.brand_id=b.id')
            ->join('LEFT JOIN sq_picture bp ON b.logo=bp.id')
            ->join('LEFT JOIN sq_merchant_shop ms ON ms.id=d.shop_id')->select(['index' => 'id']);

        //所有已确定商品的商铺ID
        $allShopId = array_keys($order);
        //找出所有商家的营业时间、配送收费等信息
        $pdo = get_pdo();
        $sql = 'SELECT id,free_delivery_amount,pay_delivery_amount,delivery_amount_cost,pay_delivery_distance,delivery_distance_cost,pay_delivery_time_begin,
        pay_delivery_time_end,delivery_time_cost,ST_Distance_Sphere(point(' . floatval($lng) . ',' . floatval($lat) . '),lnglat) distance FROM sq_merchant_shop WHERE ' .
            'id IN (' . implode(',', $allShopId) . ')';
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $_allShop = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $allShop = [];
        foreach ($_allShop as $key => $shop) {
            $allShop[$shop['id']] = &$_allShop[$key];
        }
        $priceDetail = [];//所有价格详情
        $result = [];
        $deliveryPrice = 0;//配送费总和
        $productPrice = 0;//所有商品的价格总和
        //如果用户指定了配送时间，则计算指定的配送时间距离当天的0:00的秒数；如果配送时间为0（马上配送），则计算当前时间到当天的秒数
        if ($deliveryTime) {
            $deliveryTime = $deliveryTime - strtotime(date('Y-m-d', $deliveryTime));
        } else {
            $deliveryTime = time() - strtotime(date('Y-m-d'));
        }
        //计算只是在免配送费的范围、是否在免配送费的距离内、免配送费的时间内
        /**
         * product：为当前子订单所有商品的价格总和
         * price：为免运费价格未到所收取的费用
         * distance：为送货距离超过免费距离所收取的费用
         * time：为超过正常配送时间而配送收取的费用
         */
        foreach ($order as $shopId => $o) {
            if (empty($priceDetail[$shopId])) {
                $priceDetail[$shopId] = [
                    'product' => 0,//商品价格
                    'time' => 0,//因为时间产生的配送费
                    'distance' => 0,//因为距离产生的配送费
                    'price' => 0,//因为价格产生的配送费
                    'deliveryTotal' => 0//总的配送费
                ];
            }
            foreach ($o as $d) {
                $priceDetail[$shopId]['product'] += $_allDepotInfo[$d['depot_id']]['price'] * $d['total'];
            }
            $productPrice += $priceDetail[$shopId]['product'];
            //如果是自提，则不计算配送费
            if ($deliveryMode == self::DELIVERY_MODE_PICKEDUP) continue;
            if ($priceDetail[$shopId]['product'] >= $allShop[$shopId]['free_delivery_amount']) continue;
            //如果当前商家的商品没有达到当前商家的包邮价格，则增加配送费
            if ($priceDetail[$shopId]['product'] < $allShop[$shopId]['pay_delivery_amount']) {
                $priceDetail[$shopId]['price'] = (int)$allShop[$shopId]['delivery_amount_cost'];
            }
            //如果送货距离大于商家免费送货的距离，则增加送货费
            if ($allShop[$shopId]['distance'] > $allShop[$shopId]['pay_delivery_distance']) {
                $priceDetail[$shopId]['distance'] = (int)$allShop[$shopId]['delivery_distance_cost'];
            }
            //如果现在的时间在收费配送时间内，则增加送货费
            if ($allShop[$shopId]['pay_delivery_time_begin'] < $deliveryTime && $allShop[$shopId]['pay_delivery_time_end'] > $deliveryTime) {
                $priceDetail[$shopId]['time'] = (int)$allShop[$shopId]['delivery_time_cost'];
            }
            $priceDetail[$shopId]['deliveryTotal'] = $priceDetail[$shopId]['price'] + $priceDetail[$shopId]['time'] + $priceDetail[$shopId]['distance'];
            $deliveryPrice += $priceDetail[$shopId]['deliveryTotal'];
        }
        $priceTotal = $deliveryPrice + $productPrice;
        foreach ($priceDetail as &$d) {
            $d['total'] = array_sum($d);
        }

        $allDepotInfo = [];
        foreach ($_allDepotInfo as $key => $depot) {
            if (!isset($allDepotInfo[$depot['_shop_id']])) {
                $allDepotInfo[$depot['_shop_id']] = [
                    'shop_id' => $depot['_shop_id'],
                    'shop' => $depot['_shop_title'],
                    'type' => $depot['_shop_type'],
                    'address' => $depot['_shop_address'],
                    'phone' => $depot['_shop_phone'],
                    '_prices' => $priceDetail[$depot['_shop_id']]
                ];
            }
            $allDepotInfo[$depot['_shop_id']]['_products'][] = [
                'id' => $depot['id'],
                'product_id' => $depot['product_id'],
                'price' => $depot['price'],
                'product' => $depot['title'],
                'detail' => $depot['detail'],
                'picture_path' => $depot['picture_path'],
                'brand_id' => $depot['_brand_id'],
                'brand' => $depot['_brand_title'],
                'brand_logo' => $depot['_brand_logo'],
                'total' => $products[$depot['id']]['total']
            ];
        }
        foreach ($allDepotInfo as $key => $depot) {
            $result[] = &$allDepotInfo[$key];
        }
        $cacheData = [
            'order' => &$result,
            'price_detail' => &$priceDetail,
            'price_total' => &$priceTotal,
            'delivery_mode' => $deliveryMode,
            'delivery_time' => $deliveryTime,
            'delivery_price' => $deliveryPrice
        ];
        $cacheKey = md5(serialize($cacheData));
        S($cacheKey, $cacheData, 3600);
        return [
            'price_total' => $priceTotal,//总价统计
            'price_delivery' => $deliveryPrice,//配送费统计
            'price_product' => $productPrice,//商品价格统计
            'product_total' => $productTotal,//商品数量统计
            'key' => $cacheKey,//缓存的key名
        ];
    }

    /**
     * 提交订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param string $orderKey 预处理过后的订单KEY
     * @param string $mobile 收货人联系电话
     * @param string $consignee 收货人
     * @param string $address 收货地址
     * @param string $remark 订单备注
     * @param int $payMode 支付方式
     * @return bool
     */
    public function submitOrder($userId, $orderKey, $mobile, $consignee, $address, $remark, $payMode = self::PAY_MODE_OFFLINE)
    {
        $userId = intval($userId);
        $userInfo = MemberModel::getById($userId, null, ['uid', 'nickname']);
        if (!$userInfo) E('用户：' . $userId . '不存在');
        $pretreatment = S($orderKey);
        if (!$pretreatment) E('操作超时');
        $products = $pretreatment['order'];
        $deliveryMode = intval($pretreatment['delivery_mode']);

        //声明一些模型
        $model = self::getInstance();
        $orderItemModel = M('order_item');
        $statusLogModel = OrderStatusModel::getInstance();

        $model->startTrans();//启动事务
        //判断购物车格式，如果二级数组的值还是数组，那么就是拆单的
        if (count($products) > 1) {
            try {
                $parentId = intval(self::getInstance()->createEmptyParentOrder($userId, 0, 0, $payMode, $deliveryMode));
                if (!$parentId) E('父级订单添加失败');
                $parentDeliveryPrice = 0;
                $logData = [];//记录订单状态日志的数据数组
                $pushOrderId = [];
                foreach ($products as $product) {
                    $shopId = $product['shop_id'];
                    //获取所有的仓库商品ID
                    $data = [//组合子订单数据
                        'user_id' => $userId,
                        'pid' => $parentId,
                        'shop_id' => (int)$shopId,
                        'pay_mode' => (int)$payMode,
                        'delivery_mode' => (int)$deliveryMode,
                        'delivery_time' => (int)$pretreatment['delivery_time'],
                        'mobile' => $mobile,
                        'consignee' => $consignee,
                        'address' => $address,
                        'remark' => $remark,
                        'price' => $pretreatment['price_detail'][$shopId]['total'],
                        'delivery_price' => $pretreatment['price_detail'][$shopId]['deliveryTotal']
                    ];
                    $parentDeliveryPrice += $data['delivery_price'];
                    if (!$model->create($data)) E(current($model->getError()));
                    $orderCode = $model->data()['order_code'];
                    $itemData = [];
                    if ($lastOrderId = intval($model->add())) {//如果子订单添加成功
                        $pushOrderId[] = [
                            'shop_id' => $shopId,
                            'order_id' => $lastOrderId
                        ];
                        foreach ($product['_products'] as $item) {//组合每条子订单的商品信息
                            $itemData[] = [
                                'order_id' => $lastOrderId,
                                'product_id' => $item['product_id'],
                                'depot_id' => $item['id'],
                                'price' => $item['price'],
                                'total' => $item['total']
                            ];
                        }
                        if (!$orderItemModel->addAll($itemData)) E('订单商品添加失败');
                        $logData[] = [//组合订单状态日志
                            'user_id' => $userId,
                            'shop_id' => intval($shopId),
                            'merchant_id' => 0,
                            'status' => self::STATUS_MERCHANT_CONFIRM,
                            'order_id' => $lastOrderId,
                            'content' => '系统：【' . $userInfo['nickname'] . '】于【' . date('Y - m - d H:i:s') . '】提交了订单【' . $orderCode . '】，等待商家审核',
                        ];
                    } else {
                        E('订单添加失败');
                    }
                }
                //所有子订单都插入完成之后，计算所有子订单价格的和，更新到父级订单
                if (!$model->save(['price' => $pretreatment['price_total'], 'id' => $parentId, 'delivery_price' => $parentDeliveryPrice])) {
                    E('父级订单价格更新失败');
                }
                $model->commit();//如果以上都通过了，则提交事务
                F('user / cart / ' . $userId, null);//如果订单提交成功，则清空用户的购物车
                S($orderKey, null);
                $statusLogModel->addAll($logData);
                $pushContent = '用户【' . $userInfo['nickname'] . '】提交了新订单，请您及时处理';
                $pushTitle = '您有新的订单';
                foreach ($pushOrderId as $item) {
                    push_by_uid('STORE', get_shopkeeper_by_shopid($item['shop_id']), $pushContent, [
                        'action' => 'orderDetail',
                        'order_id' => $item['order_id']
                    ], $pushTitle);
                }
                return $parentId;//返回父级订单的ID
            } catch (Exception $e) {
                //如果中途某个提交失败了，则回滚事务
                $model->rollback();
                E($e->getMessage());
            }
        } else {
            $products = current($products);
            $data['user_id'] = $userId;
            $data['pid'] = 0;
            $data['shop_id'] = $products['shop_id'];
            $data['pay_mode'] = intval($payMode);
            $data['delivery_mode'] = intval($deliveryMode);
            $data['delivery_time'] = intval($pretreatment['delivery_time']);
            $data['mobile'] = $mobile;
            $data['consignee'] = $consignee;
            $data['address'] = $address;
            $data['remark'] = $remark;
            $data['price'] = $pretreatment['price_total'];
            $data['delivery_price'] = $pretreatment['price_detail'][$data['shop_id']]['deliveryTotal'];
            try {
                if (!$model->create($data)) {
                    E(is_array($model->getError()) ? current($model->getError()) : $model->getError());
                }
                $orderCode = $model->data()['order_code'];
                if ($lastId = $model->add()) {
                    $itemData = [];
                    foreach ($products['_products'] as $product) {
                        $itemData[] = [
                            'order_id' => $lastId,
                            'product_id' => $product['product_id'],
                            'depot_id' => $product['id'],
                            'price' => $product['price'],
                            'total' => $product['total']
                        ];
                    }
                    if (!$orderItemModel->addAll($itemData)) {
                        E('订单商品添加失败');
                    }
                    //如果以上都通过了，则提交事务
                    $model->commit();
                    //如果订单提交成功，则清空用户的购物车
                    F('user / cart / ' . $userId, null);
                    S($orderKey, null);
                    //记录日志
                    $content = '系统：【' . $userInfo['nickname'] . '】于【' . date('Y - m - d H:i:s') . '】提交了订单【' . $orderCode . '】，等待商家审核';
                    //$statusLogModel->addLog($userId, $data['shop_id'], get_shopkeeper_by_shopid($data['shop_id']), $lastId, $content, self::STATUS_MERCHANT_CONFIRM);

                    $pushContent = '用户【' . $userInfo['nickname'] . '】提交了新订单，请您及时处理';
                    $pushTitle = '您有新的订单';
                    $pushExtras = [
                        'action' => 'orderDetail',
                        'order_id' => $lastId
                    ];
                    push_by_uid('STORE', get_shopkeeper_by_shopid($data['shop_id']), $pushContent, $pushExtras, $pushTitle, $pushContent);
                    return $lastId;
                }
            } catch (Exception $e) {
                //如果中途某个提交失败了，则回滚事务
                $model->rollback();
                E($e->getMessage());
            }
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
    public function createEmptyParentOrder($userId, $shopId = 0, $price = 0, $payMode = self::PAY_MODE_OFFLINE, $deliveryMode = self::DELIVERY_MODE_DELIVERY)
    {
        $data['price'] = 0;
        $data['pid'] = 0;
        $data['remark'] = '';
        $data['user_id'] = intval($userId);
        $data['shop_id'] = intval($shopId);
        $data['price'] = $price;
        $data['pay_mode'] = intval($payMode);
        $data['delivery_mode'] = intval($deliveryMode);
        $model = self::getInstance();
        if (!$model->create($data)) {
            E($model->getError());
        }
        return $model->add();
    }

    /**
     * 更新订单信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     * @param json|array $cart 购物车
     * @param null|int $payMode 支付方式
     * @param null|int $deliveryMode 配送方式
     * @param null|int $deliveryTime 配送时间
     * @param null|string|int $mobile 收货人联系方式
     * @param null|string $address 收货地址
     * @param null|string $consignee 收货人
     * @return bool
     * @throws \Exception
     */
    public function updateOrder($id, $cart = null, $payMode = null, $deliveryMode = null, $deliveryTime = null, $mobile = null, $address = null, $consignee = null)
    {
        if (!$id = intval($id)) E('订单ID非法');

        ## 初始化一些模型
        $orderModel = self::getInstance();
        $depotModel = MerchantDepotModel::getInstance();
        $orderItemModel = M('OrderItem');
        $shopModel = MerchantShopModel::getInstance();

        $pdo = get_pdo();
        //只查询状态为等待商家确认的订单，因为只有这个状态的订单才能修改
        $sth = $pdo->prepare('SELECT o.*,ms.title shop_title FROM sq_order o LEFT JOIN sq_merchant_shop ms ON ms.id=o.shop_id WHERE o.id=:id AND o.status=' . self::STATUS_MERCHANT_CONFIRM);
        $sth->execute([':id' => $id]);
        if (!$orderInfo = $sth->fetch(\PDO::FETCH_ASSOC)) E('订单不存在或不允许被修改');
        if (!$shopInfo = $shopModel->where(['id' => $orderInfo['shop_id'], 'status' => MerchantShopModel::STATUS_ACTIVE])->find()) E('店铺不存在或已关闭');
        //父级订单
        $parentOrder = $orderModel->where(['id' => $orderInfo['pid']])->find();

        $cart = is_array($cart) ? $cart : json_decode($cart, true);

        $updateData = [
            'id' => $id
        ];//要更新的订单数据

        $orderModel->startTrans();//启动事务控制
        try {
            //如果购物车不为空
            if (!empty($cart)) {
                $updateData['price'] = 0;
                $depotIds = array_column($cart, 'depot_id');
                //获取订单里的所有商品价格
                $depotsInfo = $depotModel->field(['price', 'id', 'product_id'])
                    ->where([
                        'id' => [
                            'IN',
                            $depotIds
                        ],
                        'status' => MerchantDepotModel::STATUS_ACTIVE
                    ])->select(['index' => 'id']);
                $offShelfDepotIds = [];//需要下架的仓库商品ID
                $modifyDepots = [];//需要修改的商品
                foreach ($cart as $item) {
                    if ($item['total'] == 0) {
                        $offShelfDepotIds[] = $item['depot_id'];
                    }
                    //所有被修改过的商品信息
                    $modifyDepots[] = [
                        'order_id' => $id,
                        'product_id' => $depotsInfo[$item['depot_id']]['product_id'],
                        'depot_id' => $item['depot_id'],
                        'price' => $depotsInfo[$item['depot_id']]['price'],
                        'total' => $item['total']
                    ];
                    $updateData['price'] += ($item['total'] * $depotsInfo[$item['depot_id']]['price']);
                }
                if ($orderItemModel->where(['order_id' => $id])->delete() && $orderItemModel->addAll($modifyDepots)) {
                    //订单被商家修改之后，给用户发送通知告知用户
                    $pushContent = '您的订单【' . $orderInfo['order_code'] . '】已经被商家【' . $orderInfo['shop_title'] . '】修改，请您确认商家的修改或取消订单';
                    $pushTitle = '您的订单已被修改，需要您确认';
                    $pushExtras = [
                        'action' => 'orderDetail',
                        'order_id' => $orderInfo['id']
                    ];

                    push_by_uid('CLIENT', $orderInfo['user_id'], $pushContent, $pushExtras, $pushTitle);
                    $updateData['status'] = self::STATUS_USER_CONFIRM;
                } else {
                    E('订单商品更新失败');
                }
            }

            //如果支付方式不为null
            if ($payMode !== null) {
                $updateData['pay_mode'] = $payMode;
            }

            //如果配送模式和之前订单的不一致，则重新计算配送费
            if ($deliveryMode !== null && array_key_exists($deliveryMode, self::getDeliveryModeOptions()) && $orderInfo['delivery_mode'] != $deliveryMode) {
                $updateData['delivery_mode'] = $deliveryMode;
                //如果配送模式为自提，则归零订单的配送费并重新计算订单总价
                if ($deliveryMode == self::DELIVERY_MODE_PICKEDUP) {
                    $updateData['delivery_price'] = 0;
                }
                if ($deliveryMode == self::DELIVERY_MODE_DELIVERY) {
                    //如果用户指定了配送时间，则计算指定的配送时间距离当天的0:00的秒数；如果配送时间为0（马上配送），则计算当前时间到当天的秒数
                    if ($deliveryTime) {
                        $_deliveryTime = $deliveryTime - strtotime(date('Y-m-d', $deliveryTime));
                    } else {
                        $_deliveryTime = time() - strtotime(date('Y-m-d'));
                    }
                    //如果在收费时间段内，则收取配送费
                    if ($_deliveryTime > $shopInfo['pay_delivery_time_end'] || $_deliveryTime < $shopInfo['pay_delivery_time_begin']) {
                        $updateData['price'] += $shopInfo['delivery_time_cost'];
                        $updateData['delivery_price'] += $shopInfo['delivery_time_cost'];
                    }
                }
            }

            //如果配送时间不为null（可以为0）
            if ($deliveryTime !== null && $deliveryTime != $orderInfo['delivery_time']) {
                $updateData['delivery_time'] = intval($deliveryTime);
            }

            //更新所属订单的信息
            if (!$orderModel->create($updateData)) {
                E(is_array($orderModel->getError()) ? current($orderModel->getError()) : $orderModel->getError());
            }
            $saveStatus = $orderModel->save();
            if ($saveStatus) {//如果保存成功
                if ($parentOrder) {//如果存在父级订单
                    $data = [];
                    if ($updateData['price'] != $orderInfo['price']) {
                        //父级订单的价格等于之前的价格减去当前订单之前的价格加上当前订单新的价格，没错吧？？？
                        $data['price'] = $parentOrder['price'] - $orderInfo['price'] + $updateData['price'];
                    }
                    if ($updateData['delivery_price'] != $orderInfo['delivery_price']) {
                        //父级订单的配送费等于之前的配送费-当前订单之前的配送费+新的配送费
                        $data['delivery_price'] = $parentOrder['delivery_price'] - $orderInfo['delivery_price'] + $updateData['delivery_price'];
                    }
                    if (!empty($data) && !$orderModel->where(['id' => $parentOrder['id']])->save($data)) {
                        E('订单更新失败');
                    }
                }
                $orderModel->commit();
                return true;
            } else {
                E('订单更新失败');
            }
        } catch (\Exception $e) {
            $orderModel->rollback();
            throw $e;
        } finally {

        }
    }

    /**
     * 更新订单状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $status 订单状态
     * @return bool
     */
    public function updateOrderStatus($id, $status)
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
    public function updateOrderPayMode($id, $payMode)
    {
        return self::getInstance()->where(['id' => intval($id), '_logic' => 'OR', 'pid' => intval($id)])->save(['pay_mode' => intval($payMode)]);
    }

    /**
     * 更新订单的支付方式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payStatus 支付状态
     * @return bool
     */
    public function updateOrderPayStatus($id, $payStatus)
    {
        return self::getInstance()->where(['id' => intval($id), '_logic' => 'OR', 'pid' => intval($id)])->save(['pay_status' => intval($payStatus)]);
    }

    /**
     * 商家确认订单，必须要状态为【商家确定】的订单才能执行本方法！如果确定，则更新订单为正在配送，否则更新订单为已取消
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param bool $confirm 是否确认订单
     * @param null|int $merchantId 确认者ID（商家ID）
     * @param string $content 如果$confirm为【false】，则$content不能为空
     * @return bool|int
     */
    public function MerchantConfirmOrder($id, $confirm = true, $merchantId = null, $content = '')
    {
        $id = intval($id);
        if (!$id) E('ID非法');
        if (!$confirm && $content === '') E('请说明不确定订单的原因');
        $model = self::getInstance()->where(['id' => $id, 'status' => self::STATUS_MERCHANT_CONFIRM]);
        $pdo = get_pdo();
        $sth = $pdo->prepare('SELECT o.id,o.user_id,o.shop_id,ms.title shop_title,o.order_code FROM sq_order o LEFT JOIN sq_merchant_shop ms ON o.shop_id=ms.id WHERE o.id = :id');//查找响应的订单信息
        $sth->execute([':id' => $id]);
        $orderInfo = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($confirm) {//如果确定，则更新状态为配送中，否则更新状态为用户确定
            $saveStatus = $model->save(['status' => self::STATUS_DELIVERY]);
        } else {
            $saveStatus = $model->save(['status' => self::STATUS_USER_CONFIRM]);
        }
        if (!$saveStatus) E('确认订单失败或您已经确定过订单了');
        $merchantInfo = UcenterMemberModel::get($merchantId, ['username', 'id']);//获取用户信息
        $replaceContent = '商家：【' . isset($merchantInfo['username']) ? $merchantInfo['username'] : '' . '】于【' . date('Y - m - d H:i:s') . '】%s';
        if ($confirm) {//根据是否确定来生成不同的记录信息
            $replaceStr = '确认了订单【' . $orderInfo['order_code'] . '】，商家开始发货';
            $pushContent = '商家【' . $orderInfo['shop_title'] . '】在' . date('Y-m-d H:i:s') . '确认了您的订单【' . $orderInfo['order_code'] . '】，正在为您配送^_^';
            $pushTitle = '商家已经确定了您的订单！';
            $pushExtras = [
                'action' => 'orderDetail',
                'order_id' => $orderInfo['id']
            ];
            push_by_uid('CLIENT', $orderInfo['user_id'], $pushContent, $pushExtras, $pushTitle);
        } else {
            $replaceStr = '拒绝了订单【' . $orderInfo['order_code'] . '】，原因【' . $content . '】，等待用户确认';
        }
        $logData = [//日志信息
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => $merchantId ? $merchantId : 0,
            'status' => $confirm ? self::STATUS_DELIVERY : self::STATUS_USER_CONFIRM,
            'order_id' => $orderInfo['id'],
            'content' => sprintf($replaceContent, $replaceStr)
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
//            OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }

    /**
     * 用户确认订单，必须要状态为【用户确定】的订单才能执行本方法！如果确定，则更新订单为正在配送，否则更新订单为取消
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $userId 操作者的ID
     * @param bool $confirm 是否确认订单
     * @param string $content 拒绝的理由
     * @return bool|int
     */
    public function UserConfirmOrder($id, $confirm = true, $userId = null, $content = '')
    {
        $id = intval($id);
        $content = trim($content);
        if (!$id) E('ID非法');
        if (!$confirm && $content === '') E('请说明拒绝理由');
        $model = self::getInstance()->where(['id' => $id, 'status' => self::STATUS_USER_CONFIRM]);
        $pdo = get_pdo();
        $sth = $pdo->prepare('SELECT o.id,o.order_code,o.shop_id,o.user_id,um.username,m.nickname,ms.title shop_title FROM sq_order o LEFT JOIN sq_ucenter_member um ON o.user_id=um.id LEFT JOIN sq_member m ON o.user_id=m.uid LEFT JOIN sq_merchant_shop ms ON ms.id=o.shop_id WHERE o.id=:id AND o.status = :status');
        $sth->execute([':id' => $id, ':status' => self::STATUS_USER_CONFIRM]);
        $orderInfo = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$orderInfo) E('订单不存在或状态不正确，请刷新页面重试');
        $userInfo = [];
        if ($userId) $userInfo = UcenterMemberModel::get($userId, ['id', 'username']);
        $replaceContent = '用户：【' . $userInfo['username'] . '】于【' . date('Y - m - d H:i:s') . '】%s';
        if ($confirm) {
            $saveStatus = $model->save(['status' => self::STATUS_DELIVERY]);
            $replaceStr = '确认了商家对订单的修改，商家可以进行配送';
            $pushContent = '用户【' . $orderInfo['nickname'] . '】已经确认了您对订单【' . $orderInfo['order_code'] . '】的修改，您可以开始配送此订单';
            $pushTitle = '用户确认了您对订单的修改，可以开始配送啦';
            $pushExtras = [
                'action' => 'orderDetail',
                'order_id' => $orderInfo['id']
            ];
        } else {
            $saveStatus = $model->save(['status' => self::STATUS_CANCEL]);
            $replaceStr = '拒绝了商家对订单的修改，订单被取消，原因：' . $content;
            $pushContent = '用户【' . $orderInfo['nickname'] . '】已经拒绝了您对订单【' . $orderInfo['order_code'] . '】的修改，原因：' . $content;
            $pushTitle = '用户拒绝了您对订单的修改';
            $pushExtras = [
                'action' => 'orderDetail',
                'order_id' => $orderInfo['id']
            ];
        }
        push_by_uid('STORE', get_shopkeeper_by_shopid($orderInfo['shop_id']), $pushContent, $pushExtras, $pushTitle);
        $logData = [//订单状态日志记录数据
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => 0,
            'status' => $confirm ? self::STATUS_DELIVERY : self::STATUS_CANCEL,
            'order_id' => $orderInfo['id'],
            'content' => sprintf($replaceContent, $replaceStr)
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            //OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }

    /**
     * 完成订单，必须要状态为【正在配送】的订单才能执行本方法！
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $userId 操作者的ID
     * @return bool|int
     */
    public function CompleteOrder($id, $userId)
    {
        $id = intval($id);
        if (!$id) E('ID非法');
        $model = self::getInstance();
        $orderInfo = $model->where(['id' => $id, 'status' => self::STATUS_DELIVERY])->relation('_ucenter_member')->find();//查找订单和用户数据
        //更新状态为已完成，并且支付状态为已支付
        $saveStatus = $model->where(['id' => $id, 'status' => self::STATUS_DELIVERY])->save(['status' => self::STATUS_COMPLETE, 'pay_status' => self::PAY_STATUS_TRUE]);
        $logData = [//订单状态日志记录数据
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => 0,
            'status' => self::STATUS_COMPLETE,
            'order_id' => $orderInfo['id'],
            'content' => '系统：【' . isset($orderInfo['_ucenter_member']) ? $orderInfo['_ucenter_member']['username'] : '' . '于【' . date('Y - m - d H:i:s') . '】完成了订单'
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            //OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }

    /**
     * 取消订单，必须要状态为【商家确认】或【用户确认】才能执行本方法成功！
     * @authro Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 订单ID
     * @param string $content 取消原因
     * @param int $userId 操作者ID
     * @return bool|int
     */
    public function CancelOrder($id, $content = '', $userId)
    {
        //TODO 如果已经付款，还要进行退款
        if (!$id = intval($id)) E('ID非法');
        $model = self::getInstance()->where([
            'id' => $id,
            'status' => [
                'IN',
                [
                    self::STATUS_MERCHANT_CONFIRM,
                    self::STATUS_USER_CONFIRM
                ]
            ]
        ]);
        $pdo = get_pdo();
        $sth = $pdo->prepare('SELECT ms.title shop_title,m.nickname,o.user_id,o.shop_id,o.order_code,um.username FROM sq_order o LEFT JOIN sq_merchant_shop ms ON ms.id=o.shop_id LEFT JOIN sq_member m ON m.uid=o.user_id LEFT JOIN sq_ucenter_member um ON um.id=o.user_id WHERE o.id=:id AND o.status IN (' . self::STATUS_MERCHANT_CONFIRM . ',' . self::STATUS_USER_CONFIRM . ')');
        $sth->execute([':id' => $id]);
        $orderInfo = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$orderInfo || $orderInfo['user_id'] != $userId) E('没有权限取消订单');
        $saveStatus = $model->save(['status' => self::STATUS_CANCEL]);
        $logData = [//订单状态日志记录数据
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => 0,
            'status' => self::STATUS_CANCEL,
            'order_id' => $orderInfo['id'],
            'content' => '用户：【' . $orderInfo['username'] . '于【' . date('Y - m - d H:i:s') . '】取消了订单【' . $orderInfo['order_code'] . '】'
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            //OrderStatusModel::getInstance()->add($logData);
            $shopkeeper = get_shopkeeper_by_shopid($orderInfo['shop_id']);
            $pushContent = '订单【' . $orderInfo['order_code'] . '】已经被用户【' . $orderInfo['nickname'] . '】取消，原因：' . $content ?: '未知';
            $pushTitle = '您有订单被用户取消';
            $pushExtras = [
                'action' => 'orderDetail',
                'order_id' => $id
            ];
            push_by_uid('STORE', $shopkeeper, $pushContent, $pushExtras, $pushTitle, $pushContent, $pushTitle);
        }
        return $saveStatus;
    }

    /**
     * 逻辑删除订单
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param $id
     * @param $userId
     */
    public function DeleteOrder($id, $userId)
    {
        $model = self::getInstance();
        $model->where(['id' => $id, 'status' => self::STATUS_COMPLETE]);
        $model->find();
        //TODO 这儿有个问题，如果商家和用户任何一方删除了订单，然后另一方也会变成删除，o(╯□╰)o
    }
}
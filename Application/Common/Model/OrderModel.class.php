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
 *
 * @package Common\Model
 */
class OrderModel extends RelationModel
{
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
            $products = ProductModel::getListsByProductIds($productIds, ['id', 'title', 'detail']);
            $reProducts = [];
            foreach ($products as $p) {
                $reProducts[$p['id']] = $p;
            }
            foreach ($data['_products'] as &$product) {
                $current = $reProducts[$product['product_id']];
                $product['title'] = $current['title'];
                $product['detail'] = $current['detail'];
            }
        } elseif ($getChilds) {
            $orderItemModel = M('order_item');
            foreach ($data['_childs'] as &$child) {
                $child['_products'] = $orderItemModel->field('product_id')->where(['order_id' => $child['id']])->select();
                $productIds = array_map(function ($item) {
                    return $item['product_id'];
                }, $child['_products']);
                $products = ProductModel::getListsByProductIds($productIds);
                $reProducts = [];
                foreach ($products as $p) {
                    $reProducts[$p['id']] = $p;
                }
                foreach ($child['_products'] as &$product) {
                    $current = $reProducts[$product['product_id']];
                    $product['title'] = $current['title'];
                    $product['detail'] = $current['detail'];
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
        if (!empty($shopId)) {
            $where['o.shop_id'] = intval($shopId);
            $where['o.pid'] = [//如果是根据商铺来查，那么父级订单就不用查出来了
                'NEQ',
                0
            ];
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
        if ($getShop) {
            $fields = array_merge($fields, [
                'ms.title _shop_title',
                'ms.description _shop_description',
                'ms.status _shop_status',
                'ms.type _shop_type',
                'ms.phone_number _shop_phone',
                'ms.address _shop_address',
                'ms.open_status _shop_open_status',
                'ms.region_id _shop_region_id',
                'ms_picture.path _shop_picture'
            ]);
            $model->join('LEFT JOIN sq_merchant_shop ms ON ms.id=o.shop_id');
            $model->join('LEFT JOIN sq_picture ms_picture ON ms.picture=ms_picture.id');
        }
        $total = $totalModel->count();
        $pagination = new Page($total, $pageSize);
        $data = $model->relation('_childs')->field($fields)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        $orderItemModel = M('OrderItem');
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
            if ($getShop) {
                $item['_shop'] = [
                    'id' => $item['shop_id'],
                    'title' => $item['_shop_title'],
                    'description' => $item['_shop_description'],
                    'status' => $item['_shop_status'],
                    'type' => $item['_shop_type'],
                    'phone' => $item['_shop_phone'],
                    'address' => $item['_shop_address'],
                    'open_status' => $item['_shop_open_status'],
                    'region_id' => $item['_shop_region_id'],
                    'picture' => $item['_shop_picture']
                ];
                unset($item['_shop_title'], $item['_shop_description'], $item['_shop_status'], $item['_shop_type'], $item['_shop_phone'], $item['_shop_address'], $item['_shop_open_status'], $item['_shop_region_id'], $item['_shop_picture']);
            }
            if ($getProducts) {
                if (empty($item['_childs'])) {
                    $item['_products'] = self::getProductsByOrderId($item['id']);
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
                } else {
                    $item['_products'] = [];
                    foreach ($item['_childs'] as &$child) {
                        $child['_products'] = self::getProductsByOrderId($child['id']);
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
                }
            }

            //统计目前已经确定要配货的商家要配送的货物数量【注意：这个有可能为空】
            $hasProductShopTotal = array_count_values($hasProductShopIds);
            arsort($hasProductShopTotal);//排序，方便找出要送货最多的商家
            $shopIds = array_unique($shopIds);

            //如果未分配的商品不为空
            if (!empty($notAllocationProducts)) {
                //如果配送时间不等于空
                //根据未分配商家的商品ID找出所有有这个商品的商家并按商品ID和价格排序
                $_depots = $depotModel->field('id,shop_id,price,product_id')->where([
                    'product_id' => ['IN', array_column($notAllocationProducts, 'product_id')],
                    'shop_id' => ['IN', $shopIds],
                    'status' => MerchantDepotModel::STATUS_ACTIVE
                ])->order('product_id,price')->select();
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
                $order = [];
                foreach ($products as $product) {
                    $order[$product['shop_id']][] = $product;
                }

            }
        } else {
            $order = &$cart;
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
                array_column($products, 'depot_id')]
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
                    'product' => 0,
                    'time' => 0,
                    'distance' => 0,
                    'price' => 0
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
            $deliveryPrice += $priceDetail[$shopId]['price'];
            $deliveryPrice += $priceDetail[$shopId]['time'];
            $deliveryPrice += $priceDetail[$shopId]['distance'];
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
            'delivery_time' => $deliveryTime
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
    public static function submitOrder($userId, $orderKey, $mobile, $consignee, $address, $remark, $payMode = self::PAY_MODE_OFFLINE)
    {
        $userId = intval($userId);
        $userInfo = MemberModel::getById($userId, null, ['id', 'nickname']);
        if (!$userInfo) E('用户：' . $userId . '不存在');
        $pretreatment = S($orderKey);
        if (!$pretreatment) E('请执行订单预处理');
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
                $parentId = intval(self::createEmptyParentOrder($userId, 0, 0, $payMode, $deliveryMode));
                if (!$parentId) E('父级订单添加失败');
                $logData = [];//记录订单状态日志的数据数组
                foreach ($products as $product) {
                    $shopId = $product['shop_id'];
                    //获取所有的仓库商品ID
                    $data = [//组合子订单数据
                        'user_id' => $userId,
                        'pid' => $parentId,
                        'shop_id' => (int)$shopId,
                        'pay_mode' => (int)$payMode,
                        'delivery_mode' => (int)$deliveryMode,
                        '$delivery_time' => (int)$pretreatment['delivery_time'],
                        'mobile' => $mobile,
                        'consignee' => $consignee,
                        'address' => $address,
                        'remark' => $remark,
                        'price' => $pretreatment['price_detail'][$shopId]['total'],
                    ];
                    if (!$model->create($data)) E(current($model->getError()));
                    $itemData = [];
                    if ($lastOrderId = intval($model->add())) {//如果子订单添加成功
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
                            'content' => '系统：【' . $userInfo['nickname'] . '】于【' . date('Y - m - d H:i:s') . '】提交了订单【' . $lastOrderId . '】，等待商家审核',
                        ];
                    } else {
                        E('订单添加失败');
                    }
                }
                //所有子订单都插入完成之后，计算所有子订单价格的和，更新到父级订单
                if (!$model->save(['price' => $pretreatment['price_total'], 'id' => $parentId])) {
                    E('父级订单价格更新失败');
                }
                $model->commit();//如果以上都通过了，则提交事务
                F('user / cart / ' . $userId, null);//如果订单提交成功，则清空用户的购物车
                $statusLogModel->addAll($logData);
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
            $data['shop_id'] = current($products)['shop_id'];
            $data['pay_mode'] = intval($payMode);
            $data['delivery_mode'] = intval($deliveryMode);
            $data['delivery_time'] = intval($pretreatment['delivery_time']);
            $data['mobile'] = $mobile;
            $data['consignee'] = $consignee;
            $data['address'] = $address;
            $data['remark'] = $remark;
            $data['price'] = $pretreatment['price_total'];
            try {
                if (!$model->create($data)) {
                    E(current($model->getError()));
                }
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
                    //记录日志
                    $content = '系统：【' . $userInfo['nickname'] . '】于【' . date('Y - m - d H:i:s') . '】提交了订单【' . $lastId . '】，等待商家审核';
                    $statusLogModel->addLog($userId, $data['shop_id'], 0, $lastId, $content, self::STATUS_MERCHANT_CONFIRM);
                    return intval($lastId);
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
    public static function createEmptyParentOrder($userId, $shopId = 0, $price = 0, $payMode = self::PAY_MODE_OFFLINE, $deliveryMode = self::DELIVERY_MODE_DELIVERY)
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
        return self::getInstance()->where(['id' => intval($id), '_logic' => ' OR ', 'pid' => intval($id)])->save(['status' => intval($status)]);
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
        return self::getInstance()->where(['id' => intval($id), '_logic' => ' OR ', 'pid' => intval($id)])->save(['pay_mode' => intval($payMode)]);
    }

    /**
     * 更新订单的支付方式
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param int $payStatus 支付状态
     * @return bool
     */
    public static function updateOrderPayStatus($id, $payStatus)
    {
        return self::getInstance()->where(['id' => intval($id), '_logic' => ' OR ', 'pid' => intval($id)])->save(['pay_status' => intval($payStatus)]);
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
    public static function MerchantConfirmOrder($id, $confirm = true, $merchantId = null, $content = '')
    {
        $id = intval($id);
        if (!$id) E('ID非法');
        if (!$confirm && $content === '') E('请说明不确定订单的原因');
        $model = self::getInstance()->where(['id' => $id, 'status' => self::STATUS_MERCHANT_CONFIRM]);
        $orderInfo = $model->find();//查找响应的订单信息
        if ($confirm) {//如果确定，则更新状态为配送中，否则更新状态为用户确定
            $saveStatus = $model->save(['status' => self::STATUS_DELIVERY]);
        } else {
            $saveStatus = $model->save(['status' => self::STATUS_USER_CONFIRM]);
        }
        if (!$saveStatus) E('确认订单失败');
        $merchantInfo = UcenterMemberModel::get($merchantId, ['username', 'id']);//获取用户信息
        $content = '商家：【' . isset($merchantInfo['username']) ? $merchantInfo['username'] : '' . '】于【' . date('Y - m - d H:i:s') . '】%s';
        if ($confirm) {//根据是否确定来生成不同的记录信息
            $replaceStr = '确认了订单【' . $orderInfo['id'] . '】，商家开始发货';
        } else {
            $replaceStr = '拒绝了订单【' . $orderInfo['id'] . '】，原因【' . $content . '】，等待用户确认';
        }
        $logData = [//日志信息
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => $merchantId ? $merchantId : 0,
            'status' => $confirm ? self::STATUS_DELIVERY : self::STATUS_USER_CONFIRM,
            'order_id' => $orderInfo['id'],
            'content' => sprintf($content, $replaceStr)
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }

    /**
     * 用户确认订单，必须要状态为【用户确定】的订单才能执行本方法！如果确定，则更新订单为正在配送，否则更新订单为取消
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @param bool $confirm 是否确认订单
     * @return bool|int
     */
    public static function UserConfirmOrder($id, $confirm = true, $userId = null, $content = '')
    {
        $id = intval($id);
        if (!$id) E('ID非法');
        $model = self::getInstance()->where(['id' => $id, 'status' => self::STATUS_USER_CONFIRM]);
        $orderInfo = $model->find();//查找响应的订单信息
        $userInfo = [];
        if ($userId) $userInfo = UcenterMemberModel::get($userId, ['id', 'username']);
        $content = '用户：【' . isset($userInfo['username']) ? $userInfo['username'] : '' . '】于【' . date('Y - m - d H:i:s') . '】%s';
        if ($confirm) {
            $saveStatus = $model->save(['status' => self::STATUS_DELIVERY]);
            $replaceStr = '确认了商家对订单的修改，商家可以进行配送';
        } else {
            $saveStatus = $model->save(['status' => self::STATUS_CANCEL]);
            $replaceStr = '拒绝了商家对订单的修改，订单被取消';
        }
        $logData = [//订单状态日志记录数据
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => 0,
            'status' => $confirm ? self::STATUS_DELIVERY : self::STATUS_CANCEL,
            'order_id' => $orderInfo['id'],
            'content' => sprintf($content, $replaceStr)
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }

    /**
     * 完成订单，必须要状态为【正在配送】的订单才能执行本方法！
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 订单ID
     * @return bool|int
     */
    public static function CompleteOrder($id)
    {
        $id = intval($id);
        if (!$id) E('ID非法');
        $model = self::getInstance()->where(['id' => $id, 'status' => self::STATUS_DELIVERY]);
        $orderInfo = $model->relation('_ucenter_member')->find();//查找订单和用户数据
        $saveStatus = $model->save(['status' => self::STATUS_COMPLETE]);
        $logData = [//订单状态日志记录数据
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => 0,
            'status' => self::STATUS_COMPLETE,
            'order_id' => $orderInfo['id'],
            'content' => '用户：【' . isset($orderInfo['_ucenter_member']) ? $orderInfo['_ucenter_member']['username'] : '' . '于【' . date('Y - m - d H:i:s') . '】完成了订单'
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }

    /**
     * 取消订单，必须要状态为【商家确认】或【用户确认】才能执行本方法成功！
     * @param int $id 订单ID
     * @return bool|int
     */
    public static function CancelOrder($id)
    {
        //TODO 如果已经付款，还要进行退款
        if (!$id = intval($id)) E('ID非法');
        $model = self::getInstance()->where([
            'id' => $id,
            'status' => [
                'IN' => [
                    self::STATUS_MERCHANT_CONFIRM,
                    self::STATUS_USER_CONFIRM
                ]
            ]]);
        $orderInfo = $model->relation('_ucenter_member')->find();
        $saveStatus = $model->save(['status' => self::STATUS_CANCEL]);
        $logData = [//订单状态日志记录数据
            'user_id' => $orderInfo['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'merchant_id' => 0,
            'status' => self::STATUS_CANCEL,
            'order_id' => $orderInfo['id'],
            'content' => '用户：【' . isset($orderInfo['_ucenter_member']) ? $orderInfo['_ucenter_member']['username'] : '' . '于【' . date('Y - m - d H:i:s') . '】取消了订单'
        ];
        if ($saveStatus) {//如果订单状态更新成功才存入日志
            OrderStatusModel::getInstance()->add($logData);
        }
        return $saveStatus;
    }
}
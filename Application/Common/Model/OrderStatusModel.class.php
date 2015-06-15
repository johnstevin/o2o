<?php
namespace Common\Model;

use Think\Model\RelationModel;

/**
 * @author Fufeng Nie <niefufeng@gmail.com>
 * 订单状态模型
 * @package Common\Model
 */
class OrderStatusModel extends RelationModel
{
    public static $model;//当前模型实例
    protected $autoinc = true;
    protected $fields = [
        'id',
        'user_id',
        'shop_id',
        'merchant_id',
        'status',
        'order_id',
        'content',
        'update_time',
        'update_ip',
        '_type' => [
            'id' => 'int',
            'user_id' => 'int',
            'shop_id' => 'int',
            'merchant_id' => 'int',
            'status' => 'tinyint',
            'order_id' => 'int',
            'content' => 'varchar',
            'update_time' => 'int',
            'update_ip' => 'int'
        ]
    ];

    protected $_validate = [
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
            '店铺ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'merchant_id',
            'check_merchant_exist',
            '商家用户ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'order_id',
            'check_order_exist',
            '订单ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'status',
            [
                OrderModel::STATUS_DELETE,
                OrderModel::STATUS_CANCEL,
                OrderModel::STATUS_REFUND,
                OrderModel::STATUS_DELIVERY,
                OrderModel::STATUS_COMPLETE,
                OrderModel::STATUS_USER_CONFIRM,
                OrderModel::STATUS_REFUND_COMPLETE,
                OrderModel::STATUS_MERCHANT_CONFIRM
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ]
    ];

    protected $_auto = [
        [
            'update_time',
            'time',
            self::MODEL_INSERT,
            'function'
        ],
        [
            'update_ip',
            'get_client_ip_to_int',
            self::MODEL_INSERT,
            'function'
        ]
    ];

    /**
     * 获取当前模型实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return OrderStatusModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    public function addLog($userId, $shopId, $merchantId, $orderId, $content, $status)
    {
        $data = [
            'user_id' => $userId,
            'shop_id' => $shopId,
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'content' => $content,
            'status' => $status
        ];
        $model = self::getInstance();
        if (!$model->create($data)) E(current($model->getError()));
        return $model->add();
    }
}
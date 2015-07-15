<?php
/**
 * Created by PhpStorm.
 * User: wangjiang
 * Date: 7/14/15
 * Time: 3:53 PM
 */

namespace Common\Model;

use Think\Model\AdvModel;

class OrderVehicleStatusModel extends AdvModel
{
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
            'check_order_vehicle_exist',
            '订单ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'status',
            [
                OrderVehicleModel:: STATUS_NO_WORKER,
                OrderVehicleModel:: STATUS_HAS_WORKER,
                OrderVehicleModel:: STATUS_CONFIRM,
                OrderVehicleModel:: STATUS_TREATING,
                OrderVehicleModel:: STATUS_DONE,
                OrderVehicleModel:: STATUS_CLOSED,
                OrderVehicleModel:: STATUS_CANCELED,
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
            self::MODEL_BOTH,
            'function'
        ],
        [
            'update_ip',
            'get_client_ip_to_int',
            self::MODEL_INSERT,
            'function'
        ]
    ];

}
<?php
namespace Common\Model;

use Think\Model\AdvModel;
use Think\Page;

/**
 * 商家仓库模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class MerchantDepotModel extends AdvModel
{
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭

    protected $fields = [
        'merchant_id',
        'product_id',
        'status',
        'price',
        'add_time',
        'add_ip',
        'remark',
        'update_time',
        'update_ip',
        '_type' => [
            'merchant_id' => 'int',
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
    protected $readonlyField = ['merchant_id', 'product_id', 'add_time', 'add_ip'];

    protected $_validate = [
        [
            'merchant_id',
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
            'merchant_id',
            'checkMerchantExist',
            '有非法商家ID',
            self::MUST_VALIDATE,
            'callback'
        ],
        [
            'product_id',
            'checkProductExist',
            '有非法商品ID',
            self::MUST_VALIDATE,
            'callback'
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
    protected static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 验证商家是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int|array $merchantIds 商家的ID或ID数组
     * @return bool
     */
    protected function checkMerchantExist($merchantIds)
    {
        $ids = is_array($merchantIds) ? $merchantIds : explode(',', $merchantIds);
        $ids = array_unique($ids);
        // TODO:待完善
        return true;
    }

    /**
     * 验证商家是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int|array $productIds 商品的ID或ID数组
     * @return bool
     */
    protected function checkProductExist($productIds)
    {
        $ids = is_array($productIds) ? $productIds : explode(',', $productIds);
        $ids = array_unique($ids);
        // TODO:待完善
        return true;
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
        if (!$where['id'] || !MerchantModel::checkMerchantExist($where['id'])) return ['data' => [], 'pagination' => ''];
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
}
<?php
namespace Common\Model;

use Think\Model\RelationModel;
use Think\Page;

/**
 * 购物评价表
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class AppraiseModel extends RelationModel
{
    protected static $model;
    protected $autoinc = true;
    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_ACTIVE = 1;//正常
    ## 匿名状态常量
    const ANONYMITY_TRUE = 1;//匿名评论
    const ANONYMITY_FALSE = 0;//公开评论

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
        'type',
        'anonymity',
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
            'status' => 'tinyint',
            'type' => 'tinyint',
            'anonymity' => 'tinyint'
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
            'check_shop_exist',
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
        //TODO 目前没有送货员，等员工管理完成后再说
//        [
//            'merchant_id',
//            'check_merchant_exist',
//            'merchantId非法',
//            self::EXISTS_VALIDATE,
//            'function'
//        ],
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
        ],
        [
            'anonymity',
            [
                self::ANONYMITY_TRUE,
                self::ANONYMITY_FALSE
            ],
            '匿名类型非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
    ];

    protected $_auto = [
        [
            'status',
            self::STATUS_ACTIVE
        ],
        [
            'update_time',
            'time',
            self::MODEL_BOTH,
            'function'
        ],
    ];

    /**
     * 获得所有的状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '已删除',
            self::STATUS_ACTIVE => '正常'
        ];
    }

    /**
     * 获得所有匿名的选项
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getAnonymityOptions()
    {
        return [
            self::ANONYMITY_FALSE => '公开评价',
            self::ANONYMITY_TRUE => '匿名评价'
        ];
    }

    /**
     * 获取评论列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $shopId 商铺ID
     * @param null|int $userId 用户ID
     * @param null|int $merchantId 商铺ID
     * @param null|int $status 状态
     * @param bool $getUser 是后关联查询用户信息
     * @param bool $getShop 是否关联查询商铺信息
     * @param bool $getOrder 是否关联订单
     * @param string|array $files 要查询的字段
     * @param int $pageSize 分页大小
     * @return array
     */
    public static function getLists($shopId = null, $userId = null, $merchantId = null, $status = null, $getUser = false, $getShop = false, $getOrder = false, $files = '*', $pageSize = 10)
    {
        $where = [];
        $relation = [];
        if (!empty($shopId)) $where['shop_id'] = intval($shopId);
        if (!empty($userId)) $where['user_id'] = intval($userId);
        if (!empty($merchantId)) $where['merchant_id'] = intval($merchantId);
        if ($status !== null && array_key_exists($status, self::getStatusOptions())) {
            $where['status'] = $status;
        } else {
            $where['status'] = ['NEQ', self::STATUS_DELETE];
        }
        //关联用户
        if ($getUser) $relation[] = '_user';
        //关联商铺
        if ($getShop) $relation[] = '_shop';
        //关联订单
        if ($getOrder) $relation[] = '_order';
        $model = self::getInstance();
        $total = $model->where($where)->count('id');
        $pagination = new Page($total, $pageSize);
        $lists = $model->field($files)->where($where)->limit($pagination->firstRow . ',' . $pagination->listRows)->select();
        return [
            'data' => $lists,
            'pagination' => $pagination->show()
        ];
    }

    /**
     * 根据商铺ID获得评论列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $shopId 商铺ID
     * @param int $pageSize 分页大小
     * @param bool $getUser 是否关联获取用户信息
     * @param string|array $fileds 要查询的字段
     * @return array
     */
    public static function getListsByShopId($shopId, $pageSize = 10, $getUser = true, $fileds = '*')
    {
        return self::getLists($shopId, null, null, null, $getUser, false, false, $fileds, $pageSize);
    }

    /**
     * 根据用户ID查询评论列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param int $pageSize 分页大小
     * @param bool $getShop 是否关联查询商铺信息
     * @param bool $getOrder 是否关联查询订单
     * @param string|array $fileds 要查询的字段
     * @return array
     */
    public static function getListsByUserId($userId, $pageSize = 10, $getShop = true, $getOrder = false, $fileds = '*')
    {
        return self::getLists(null, $userId, null, null, null, $getShop, $getOrder, $fileds, $pageSize);
    }

    public static function getListsByMerchantId()
    {
        //TODO 待码代码。。。
    }

    /**
     * 添加评论
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $orderId 订单ID
     * @param int $shopId 商铺ID
     * @param int $userId 用户ID
     * @param int $merchantId merchat ID
     * @param string $content 评论内容
     * @param float $grade1 评论星星
     * @param float $grade2 评论星星
     * @param float $grade3 评论星星
     * @param int $anonymity 是否匿名
     * @return bool|int 如果添加成功返回添加成功的ID，否则返回false
     */
    public static function addAppraise($orderId, $shopId, $userId, $merchantId, $content, $grade1, $grade2, $grade3, $anonymity = 0)
    {

        $data = [
            'order_id' => intval($orderId),
            'shop_id' => intval($shopId),
            'user_id' => intval($userId),
            'merchant_id' => intval($merchantId),
            'content' => trim($content),
            'grade_1' => floatval($grade1),
            'grade_2' => floatval($grade2),
            'grade_3' => floatval($grade3),
            'anonymity' => intval($anonymity),
        ];
//E(json_encode($data));
        $model = self::getInstance();
        if (!$model->create($data))
            E(is_array($model->getError()) ? current($model->getError()) : $model->getError());//如果验证不通过，直接抛出异常
        return $model->add();
    }

    /**
     * 删除评论
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 评论ID
     * @param bool $logic 是否逻辑删除
     * @return bool|int 删除成功则返回删除成功的条数，否则返回false
     */
    public static function deleteAppraise($id, $logic = true)
    {
        if ($logic) {
            return self::getInstance()->save(['id' => intval($id), 'status' => self::STATUS_DELETE]);
        }
        return self::getInstance()->delete(intval($id));
    }

}

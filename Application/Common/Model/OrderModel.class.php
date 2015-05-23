<?php
namespace Common\Model;

use Think\Model\AdvModel;

class OrderModel extends AdvModel
{
    protected static $model;

    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_CLOSE = 0;//关闭
    const STATUS_ACTIVE = 1;//正常

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
            'user_id',
            'check_user_exist',
            '用户不存在',
            self::EXISTS_VALIDATE,
            'function'
        ]
    ];

    protected $_auto = [
        [
            'create_time',
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
            'status',
            self::STATUS_ACTIVE,
            self::MODEL_INSERT
        ],
        [
            'reply',
            self::REPLY_ALLOW,
            self::REPLY_ALLOW
        ],
        [
            'pid',
            0,
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
     * 获取所有分类的状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '逻辑删除',
            self::STATUS_CLOSE => '关闭',
            self::STATUS_ACTIVE => '正常'
        ];
    }

    /**
     * 获取display所有选项
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getDisplayOptions()
    {
        return [
            self::DISPLAY_HIDDEN => '隐藏',
            self::DISPLAY_SHOW => '显示'
        ];
    }

    /**
     * 获取回复权限的所有选项
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getReplyOptions()
    {
        return [
            self::REPLY_ALLOW => '允许',
            self::REPLY_DENY => '禁止'
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
     * 验证分类是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     * @return bool
     */
    protected static function checkCategoryExist($id)
    {
        return ($id == 0 || self::getInstance()->field('id')->where(['id' => $id, 'status' => self::STATUS_ACTIVE])->find()) ? true : false;
    }

    /**
     * 获得分组列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return null|array
     */
    public static function getLists()
    {
        return self::getInstance()->where(['status' => self::STATUS_ACTIVE])->order('order DESC')->select();
    }

    /**
     * 获取分类树
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $parentId 父级ID
     * @return array
     */
    public static function getTree($parentId = 0)
    {
        $categorys = S('sys_category_tree');
        if (!isset($categorys[$parentId])) {
            $categorys[$parentId] = list_to_tree(self::getLists(), 'id', 'pid', '_child', $parentId);
            S('sys_category_tree', $categorys);
        }
        return $categorys[$parentId];
    }

    /**
     * 根据ID获取分类信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 分类ID
     * @return array|null
     */
    public static function get($id)
    {
        $id = intval($id);
        return $id ? self::getInstance()->where(['id' => $id, 'status' => self::STATUS_ACTIVE])->find() : null;
    }
}
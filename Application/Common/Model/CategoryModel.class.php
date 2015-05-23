<?php
namespace Common\Model;

use Think\Model\AdvModel;

class CategoryModel extends AdvModel
{
    protected static $model;

    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_CLOSE = 0;//关闭
    const STATUS_ACTIVE = 1;//正常
    ## 显示常量
    const DISPLAY_SHOW = 1;//显示
    const DISPLAY_HIDDEN = 0;//隐藏
    ## 是否允许回复
    const REPLY_ALLOW = 1;//允许
    const REPLY_DENY = 0;//不允许
    ## 是否需要审核
    const CHECK_ENABLE = 1;//需要审核
    const CHECK_DISABLE = 0;//不需要审核

    protected $fields = [
        'id',
        'title',
        'pid',
        'sort',
        'list_row',
        'description',
        'display',
        'reply',
        'check',
        'extend',
        'create_time',
        'update_time',
        'status',
        'icon',
        '_type' => [
            'id' => 'int',
            'title' => 'varchar',
            'pid' => 'int',
            'sort' => 'int',
            'list_row' => 'tinyint',
            'description' => 'varchar',
            'display' => 'tinyint',
            'replay' => 'tinyint',
            'check' => 'tinyint',
            'extend' => 'longtext',
            'create_time' => 'int',
            'update_time' => 'int',
            'status' => 'tinyint',
            'icon' => 'varchar'
        ]
    ];

    protected $readonlyField = [
        'id',
        'create_time'
    ];

    protected $_validate = [
        [
            'title',
            'require',
            '分类名称不能为空',
            self::MUST_VALIDATE,
        ],
        [
            'title',
            '',
            '分类名称已经存在',
            self::MUST_VALIDATE,
            'unique'
        ],
        [
            'pid',
            'checkCategoryPidExist',
            '父级ID非法',
            self::MUST_VALIDATE,
            'callback'
        ],
        [
            'sort',
            'number',
            '排序格式错误'
        ],
        [
            'list_row',
            'number',
            '每页行数必须为数字'
        ],
        [
            'reply',
            [
                self::REPLY_ALLOW,
                self::REPLY_DENY
            ],
            '允许回复非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'check',
            [
                self::CHECK_DISABLE,
                self::CHECK_ENABLE
            ],
            '审核开关非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'status',
            [
                self::STATUS_DELETE,
                self::STATUS_CLOSE,
                self::STATUS_ACTIVE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
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
    public static function checkCategoryExist($id)
    {
        $id = intval($id);
        return ($id && self::get($id, 'id')) ? true : false;
    }

    /**
     * 检测父级ID是否合法
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $pid 父级ID
     * @return bool
     */
    public static function checkCategoryPidExist($pid)
    {
        return ($pid == 0 || self::checkCategoryExist($pid)) ? true : false;
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
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $fields = '*')
    {
        $id = intval($id);
        return $id ? self::getInstance()->field($fields)->where(['id' => $id, 'status' => self::STATUS_ACTIVE])->find() : null;
    }
}
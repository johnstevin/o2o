<?php
namespace Common\Model;

use Think\Model\AdvModel;

class CategoryModel extends AdvModel
{
    protected static $model;
    protected $pk = 'id';
    protected $autoinc = true;

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
        'level',
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
            'level' => 'tinyint',
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
            self::MODEL_BOTH,
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
        ],
        [
            'level',
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
     * @param int|null $pid 父级ID，为空则不限制
     * @param int|null $level 层级，为空则不限制
     * @param string|array $fields 要查询的字段
     * @param null|int $pageSize 分页大小
     * @return null|array
     */
    public static function getLists($pid = null, $level = null, $pageSize = null, $fields = '*')
    {
        $where = [
            'status' => self::STATUS_ACTIVE
        ];
        $nowPage = empty($_GET['p']) ? 1 : intval($_GET['p']);
        if ($pid !== null) $where['pid'] = intval($pid);
        if ($level !== null) {
            $level = is_array($level) ? $level : explode(',', $level);
            $where['level'] = [
                'IN',
                $level
            ];
        }
        $model = self::getInstance()->where($where)->field($fields);
        if ($pageSize) {
            $model->page($nowPage, $pageSize);
        }
        return [
            'data' => $model->order(['sort'])->select()
        ];
    }

    /**
     * 根据ID获取分类列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string|array $ids IDs
     * @param string|array $fields 要查询的字段
     * @return null|array
     */
    public static function getListsByIds($ids, $fields = '*')
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $ids = array_unique($ids);
        return self::getInstance()->field($fields)->where(['status' => self::STATUS_ACTIVE, 'id' => ['IN', $ids]])->select();
    }

    /**
     * 获取分类树
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $parentId 父级ID
     * @param null|string|array
     * @param string|array $fields 要查询的字段（请注意至少要查询id、pid两个字段）
     * @return array
     */
    public static function getTree($parentId = 0, $level = null, $fields = '*')
    {
        //缓存一周（如果后台更新了分类数据，应该手动点击清理缓存）
        $level = is_array($level) ? implode('_', $level) : $level;
        $key = 'sys_category_tree_' . $parentId . trim($level);
        $categorys = S($key);
        if (empty($categorys)) {
            $categorys = list_to_tree(self::getLists(null, $level, null, $fields)['data'], 'id', 'pid', '_childs', $parentId);
            S($key, $categorys, 604800);
        }
        return ['data' => $categorys];
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

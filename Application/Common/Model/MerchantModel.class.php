<?php
namespace Common\Model;

use Think\Exception;
use Think\Model\AdvModel;

/**
 * 商家模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class MerchantModel extends AdvModel
{
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常
    const STATUS_CLOSE = 0;//关闭

    //模型的字段
    protected $fields = [
        'id',
        'description',
        'pid',
        'login',
        'last_login_ip',
        'last_login_time',
        'status',
        '_type' => [
            'id' => 'int',
            'description' => 'varchar',
            'pid' => 'int',
            'login' => 'int',
            'last_login_ip' => 'int',
            'last_login_time' => 'int',
            'status' => 'tinyint'
        ]
    ];
    /**
     * 只读字段
     * @var array
     */
    protected $readonlyField = ['id'];

    /**
     * 自动验证
     * @var array
     */
    protected $_validate = [
        [
            'title',
            'require',
            '标题不能为空',
            self::MUST_VALIDATE
        ],
        [
            'brand_id',
            'number',
            '品牌ID类型非法'
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
        ],


        [
            'title',
            'require',
            '商家名称不能为空',
            self::MUST_VALIDATE
        ],
        [
            'pid',
            'checkMerchantPidExist',
            '父级ID非法',
            self::MUST_VALIDATE,
            'callback'
        ],
        [
            'last_login_ip',
            'checkIpFormat',
            'IP地址格式不正确',
            self::VALUE_VALIDATE,
            'function'
        ],
        [
            'status',
            [
                self::STATUS_ACTIVE,
                self::STATUS_CLOSE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ]
    ];

    /**
     * 自动完成
     * @var array
     */
    protected $_auto = [
        [
            'status',
            self::STATUS_ACTIVE,
            self::MODEL_INSERT
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
     * @return MerchantModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
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
     * 检测商家是否存在
     * @param int $id 商家ID
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return bool
     */
    public static function checkMerchantExist($id)
    {
        $id = intval($id);
        return self::get($id, 'id') ? true : false;
    }

    /**
     * 检测父级ID是否合法
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $pid 父级ID
     * @return bool
     */
    public static function checkMerchantPidExist($pid)
    {
        return ($pid == 0 || self::checkMerchantExist($pid)) ? true : false;
    }

    /**
     * 根据ID获取商家信息
     * @param int $id 商家ID
     * @param string|array $fields 要查询的字段
     * @return array|null
     */
    public static function get($id, $fields = '*')
    {
        $id = intval($id);
        return $id ? self::getInstance()->field($fields)->where(['status' => self::STATUS_ACTIVE, 'id' => $id])->find() : null;
    }

    // TODO 这个因为要算距离，我做不到
    public static function getLists()
    {
    }

    public function getInfos( $uid, $field = '*' ){
        try{
            $userInfo=$this
                ->field($field)
                ->table('__UCENTER_MEMBER__ a')
                ->join('__MERCHANT__ b ON  a.id = b.id','LEFT')
                ->where(array('a.is_merchant'=>array('eq', '1'),'a.id'=>$uid))
                ->select();
            if(empty($userInfo))
                E(-1);
            return $userInfo;

        }catch (\Exception $ex){
            return $ex->getMessage();
        }
    }

}
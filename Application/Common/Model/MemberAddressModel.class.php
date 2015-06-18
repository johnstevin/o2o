<?php
namespace Common\Model;

use Think\Model\RelationModel;

/**
 * Class MemberAddressModel
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @property int $id ID
 * @property int $uid 用户ID
 * @property string $name 收货人姓名
 * @property int $region_id 行政区ID
 * @property string $address 收货地址
 * @property string $mobile 联系方式
 * @property int $status 状态
 *
 * @property MemberModel[] $_user
 * @package Common\Model
 */
class MemberAddressModel extends RelationModel
{
    protected static $model;
    protected $autoinc = true;

    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_ACTIVE = 1;//正常
    ## 默认常量
    const DEFAULT_TRUE = 1;//默认地址
    const DEFAULT_FALSE = 0;//不是默认地址

    /**
     * 获得当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return MemberAddressModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    protected $fields = [
        'id',
        'uid',
        'name',
        'region_id',
        'address',
        'mobile',
        'status',
        'lnglat',
        'default',
        '_type' => [
            'id' => 'int',
            'uid' => 'int',
            'name' => 'varchar',
            'region_id' => 'int',
            'address' => 'varchar',
            'mobile' => 'char',
            'status' => 'tinyint',
            'lnglat' => 'point',
            'default' => 'tinyint'
        ]
    ];

    /**
     * 验证规则
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $_validate = [
        [
            'uid',
            'check_user_exist',
            '用户ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'status',
            [
                self::STATUS_DELETE,
                self::STATUS_ACTIVE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'region_id',
            'check_region_exist',
            '行政区域ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'name',
            'require',
            '姓名不能为空',
            self::MUST_VALIDATE,
        ],
        [
            'address',
            'require',
            '地址不能为空',
            self::MUST_VALIDATE
        ],
        [
            'mobile',
            'require',
            '联系方式不能为空',
            self::MUST_VALIDATE
        ]
    ];

    protected $_auto = [
        [
            'default',
            self::DEFAULT_FALSE,
            self::MODEL_INSERT
        ]
    ];

    /**
     * 关联模型
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $_link = [
        'User' => [
            'mapping_type' => self::BELONGS_TO,
            'class_name' => 'Member',
            'foreign_key' => 'uid',
            'mapping_name' => '_user',
            'condition' => 'status !=' . self::STATUS_DELETE
        ]
    ];

    /**
     * 获得所有的状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '删除',
            self::STATUS_ACTIVE => '正常'
        ];
    }

    /**
     * 根据ID获得单条地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址ID
     * @param string|array $files 要查询的字段
     * @return null|array
     */
    public static function getById($id, $files = '*')
    {
        return self::getInstance()->field($files)->where(['id' => intval($id), 'status' => self::STATUS_ACTIVE])->find();
    }

    /**
     * 检测地址是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址ID
     * @return bool
     */
    public static function checkAddressExist($id)
    {
        $id = intval($id);
        return ($id && self::getById($id, 'id')) ? true : false;
    }

    /**
     * 获取地址列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|int $uid 用户ID
     * @param null|string $name 收货人姓名
     * @param null|int $regionId 区域ID
     * @param int $status 状态
     * @param string|array $fields 要查询的字段
     * @return null|array
     */
    public static function getLists($uid = null, $name = null, $regionId = null, $status = self::STATUS_ACTIVE, $fields = '*')
    {
        $where = [];
        if ($uid !== null) $where['uid'] = intval($uid);
        if (!empty($name)) $where['name'] = trim($name);
        if ($regionId !== null) $where['region_id'] = intval($regionId);
        if ($status && in_array($status, array_keys(self::getStatusOptions()))) {
            $where['status'] = $status;
        } else {
            $where['status'] = self::STATUS_ACTIVE;
        }
        return [
            'data' => self::getInstance()->field($fields)->where($where)->select()
        ];
    }

    /**
     * 添加地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $uid 用户ID
     * @param string $name 收货人姓名
     * @param string $address 收货地址
     * @param string|int $mobile 收货人联系方式
     * @param int $regionId 区域ID
     * @param float $lng 经度
     * @param float $lat 纬度
     * @return int|bool 添加成功返回地址ID，否则返回false
     */
    public function addAddress($uid, $name, $address, $mobile, $regionId, $lng, $lat)
    {
        $data = [
            'uid' => intval($uid),
            'name' => trim($name),
            'address' => trim($address),
            'mobile' => $mobile,
            'region_id' => intval($regionId),
            'lnglat' => 'POINT(' . floatval($lng) . ' ' . floatval($lat) . ')'
        ];
        $model = self::getInstance();
        if (!$model->create($data)) {
            E(is_array($model->getError()) ? current($model->getError()) : $model->getError());
        }
        $field = [];
        $bind = [];
        foreach ($data as $key => $item) {
            $bindName = ':' . $key;
            if ($this->fields['_type'][$key] == 'point') {
                $field[] = 'st_geomfromtext(' . $bindName . ')';
            } else {
                $field[] = $bindName;
            }
            $bind[$bindName] = $item;
        }
        $pdo = new \PDO(C('DB_TYPE') . ':host=' . C('DB_HOST') . ';dbname=' . C('DB_NAME'), C('DB_USER'), C('DB_PWD'));
        $pdo->exec('SET NAMES ' . C('DB_CHARSET'));
        $sql = 'INSERT INTO ' . self::getInstance()->getTableName() . '(' . implode(',', array_keys($data)) . ') VALUES('
            . implode(',', $field) . ')';
        $sth = $pdo->prepare($sql);
        $sth->execute($bind);
        $lastId = (int)$pdo->lastInsertId();
        unset($pdo);
        return $lastId;
    }

    /**
     * 更新地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址的ID
     * @param string $name 收货人姓名
     * @param string $address 收货地址
     * @param int|string $mobile 收货人联系方式
     * @param int $regionId 区域ID
     * @param float $lng 经度
     * @param float $lat 纬度
     * @return bool 是否更新成功
     */
    public function updateAddress($id, $name = null, $address = null, $mobile = null, $regionId = null, $lng = null, $lat = null)
    {
        $model = self::getInstance();
        $data = [];
        ## 组合要更新的数据
        if (!empty($name)) $data['name'] = trim($name);
        if (!empty($address)) $data['address'] = trim($address);
        if (!empty($mobile)) $data['mobile'] = $mobile;
        if (!empty($regionId)) $data['region_id'] = intval($regionId);
        if (!empty($lng) && !empty($lat)) $data['lnglat'] = 'POINT(' . floatval($lng) . ' ' . floatval($lat) . ')';
        if (!$model->create($data)) {//利用模型的规则检测数据是否合法
            E(is_array($model->getError()) ? current($model->getError()) : $model->getError());
        }
        $field = [];//要更新的字段
        $bind = [];//要绑定的数据
        foreach ($data as $key => $item) {
            if ($item === null) continue;
            $bindName = ':' . $key;
            if ($this->fields['_type'][$key] == 'point') {
                $field[] = $key . '=st_geomfromtext(' . $bindName . ')';
            } else {
                $field[] = $key . '=' . $bindName;
            }
            $bind[$bindName] = $item;
        }
        $bind[':id'] = intval($id);
        $pdo = new \PDO(C('DB_TYPE') . ':host=' . C('DB_HOST') . ';dbname=' . C('DB_NAME'), C('DB_USER'), C('DB_PWD'));
        $pdo->exec('SET NAMES ' . C('DB_CHARSET'));
        $sql = 'UPDATE ' . self::getInstance()->getTableName() . ' SET ' . implode(',', $field) . ' WHERE id = :id';
        $sth = $pdo->prepare($sql);
        $result = $sth->execute($bind);
        unset($pdo);
        return $result;
    }

    /**
     * 删除地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址ID
     * @param bool $logic 是否逻辑删除
     * @return bool|int 返回删除成功的记录数或者false
     */
    public static function deleteAddress($id, $logic = true)
    {
        $id = intval($id);
        if ($logic) {//逻辑删除则更新地址状态
            return self::getInstance()->where(['id' => $id])->save(['status' => self::STATUS_DELETE]);
        }
        return self::getInstance()->delete($id);//否则物理删除地址
    }

    /**
     * 恢复逻辑删除的地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 要恢复的地址ID
     * @return bool
     */
    public static function activeAddress($id)
    {
        return self::getInstance()->where(['id' => intval($id)])->save(['status' => self::STATUS_ACTIVE]);
    }
}
<?php
namespace Common\Model;

use Think\Model\AdvModel;

/**
 * 用户停车地址模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class MemberAddressVehideModel extends AdvModel
{
    /**
     * 当前模型实例
     * @var self
     */
    protected static $instance;
    ## 定义一些常量
    const DEFAULT_TRUE = 1;//默认地址
    const DEFAULT_FALSE = 0;//不是默认地址

    ## 状态常量
    const STATUS_DELETE = -1;//已删除
    const STATUS_ACTIVE = 1;//正常
    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 主键时候自增
     * @var bool
     */
    protected $autoinc = true;

    /**
     * 模型字段
     * @var array
     */
    protected $fields = [
        'id',
        'user_id',
        'region_id',
        'car_number',
        'lnglat',
        'address',
        'status',
        'default',
        'picture_id',
        'street_number',
        '_type' => [
            'id' => 'int',
            'user_id' => 'int',
            'region_id' => 'int',
            'car_number' => 'varchar',
            'lnglat' => 'point',
            'address' => 'varchar',
            'status' => 'tinyint',
            'default' => 'tinyint',
            'picture_id' => 'int',
            'street_number' => 'varchar'
        ]
    ];

    /**
     * @var array
     */
    protected $_validate = [
        [
            'user_id',
            'check_user_exist',
            '用户ID非法',
            self::MUST_VALIDATE,
            'function'
        ],
        [
            'region_id',
            'check_region_exist',
            '区域ID非法',
            self::EXISTS_VALIDATE,
            'function'
        ],
        [
            'status',
            [
                self::STATUS_ACTIVE,
                self::STATUS_DELETE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in',
            self::MODEL_UPDATE
        ],
        [
            'default',
            [
                self::DEFAULT_FALSE,
                self::DEFAULT_TRUE
            ],
            '状态非法',
            self::EXISTS_VALIDATE,
            'in'
        ],
        [
            'car_number',
            'require',
            '车牌号不能为空',
            self::MUST_VALIDATE
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
            self::MODEL_INSERT,
        ]
    ];

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return \Common\Model\MemberAddressVehideModel
     */
    public static function getInstance()
    {
        return self::$instance instanceof self ? self::$instance : self::$instance = new self;
    }

    /**
     * 获取当前模型所有的状态配置
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
     * 添加地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param string $carNumber 车牌号
     * @param string $address 地址
     * @param int|bool $isDefault 是否默认
     * @param int $pictureId 图片ID
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param string $streetNumber 门牌号
     * @return string
     */
    public function addAddress($userId, $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber)
    {
        $pdo = get_pdo();
        $data = [
            'user_id' => $userId,
            'car_number' => $carNumber,
            'address' => $address,
            'default' => $isDefault,
            'picture_id' => $pictureId,
            'street_number' => $streetNumber
        ];
        $model = self::getInstance();
        if (!$model->create($data)) E(is_array($model->getError()) ? current($model->getError()) : $model->getError());
        $bind = [
            ':user_id' => $userId,
            ':car_number' => $carNumber,
            ':address' => trim($address),
            ':isDefault' => intval($isDefault),
            ':picture_id' => $pictureId,
            ':lng' => floatval($lng),
            ':lat' => floatval($lat),
            ':street_number' => trim($streetNumber)
        ];
        $sql = 'INSERT INTO sq_member_address_vehide (user_id, region_id, car_number, lnglat, address, `default`, picture_id) VALUES (:user_id,:region_id,:car_number,point(:lng,:lat),:address,:isDefault,:street_number)';
        $sth = $pdo->prepare($sql);
        $sth->execute($bind);
        return $pdo->lastInsertId();
    }

    /**
     * 获取列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $userId 用户ID
     * @param null $status 状态
     * @param int $pageSize 分页大小
     * @return array
     */
    public function getList($userId, $status = null, $pageSize = 10)
    {
        $nowPage = $_GET['p'] ?: 1;
        $pdo = get_pdo();
        $bind = [
            ':user_id' => intval($userId)
        ];
        $sql = 'SELECT mav.user_id,mav.id,mav.address,mav.car_number,mav.status,mav.region_id,picture.path picture,mav.`default` FROM sq_member_address_vehide mav LEFT JOIN sq_picture picture ON mav.picture_id=picture.id WHERE mav.user_id = :user_id AND ';
        if ($status !== null && array_key_exists($status, self::getStatusOptions())) {
            $sql .= 'mav.status = :status';
            $bind[':status'] = intval($status);
        } else {
            $sql .= 'mav.status != :status';
            $bind[':status'] = self::STATUS_DELETE;
        }
        $sth = $pdo->prepare($sql);
        $sth->execute($bind);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
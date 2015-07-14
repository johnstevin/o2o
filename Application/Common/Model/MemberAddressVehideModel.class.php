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
        'mobile',
        'picture_id',
        'street_number',
        '_type' => [
            'id' => 'int',
            'user_id' => 'int',
            'region_id' => 'int',
            'car_number' => 'varchar',
            'lnglat' => 'point',
            'address' => 'varchar',
            'mobile' => 'varchar',
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
            'function',
            self::MODEL_INSERT
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
            self::MUST_VALIDATE,
            '',
            self::MODEL_INSERT
        ],
        [
            'car_number',
            'require',
            '车牌号不能为空',
            self::EXISTS_VALIDATE,
            '',
            self::MODEL_UPDATE
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
     * @param string|int $mobile 联系电话
     * @return string
     */
    public function addAddress($userId, $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber, $mobile)
    {
        $pdo = get_pdo();
        $data = [
            'user_id' => $userId,
            'car_number' => $carNumber,
            'address' => $address,
            'default' => $isDefault,
            'picture_id' => $pictureId,
            'street_number' => $streetNumber,
            'mobile' => $mobile
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
            ':street_number' => trim($streetNumber),
            ':status' => self::STATUS_ACTIVE,
            ':mobile' => $mobile
        ];
        if ($isDefault) {
            $model->where(['user_id' => $userId])->save(['default' => self::DEFAULT_FALSE]);
        }
        $sql = 'INSERT INTO sq_member_address_vehide (user_id, car_number, lnglat, address, `default`, picture_id,street_number,status,mobile) VALUES (:user_id,:car_number,point(:lng,:lat),:address,:isDefault,:picture_id,:street_number,:status,:mobile)';
        $sth = $pdo->prepare($sql);
        $sth->execute($bind);
        return $pdo->lastInsertId();
    }

    /**
     * 更新地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 地址ID
     * @param null|string $carNumber 车牌号
     * @param null|string $address 地址
     * @param null|int|bool $isDefault 是否设为默认
     * @param null|int $pictureId 图片ID
     * @param null|string $streetNumber 门牌号
     * @param null|float $lng 经度
     * @param null|float $lat 纬度
     * @param string|int $mobile 联系电话
     * @return bool
     */
    public function updateAddress($id, $carNumber = null, $address = null, $isDefault = null, $pictureId = null, $streetNumber = null, $lng = null, $lat = null, $mobile = null)
    {
        $data = ['id' => intval($id)];
        if (!$id = intval($id)) E('id非法');
        if (!empty($carNumber)) $data['car_number'] = trim($carNumber);
        if (!empty($address)) $data['address'] = trim($address);
        if (!empty($pictureId)) $data['picture_id'] = intval($pictureId);
        if (!empty($streetNumber)) $data['street_number'] = trim($streetNumber);
        if ($isDefault !== null) $data['default'] = intval($isDefault);
        if (!empty($lng) && !empty($lat)) {
            $data['lnglat'] = floatval($lng) . ',' . floatval($lat);
        }
        if (!empty($mobile)) $data['mobile'] = $mobile;
        if (empty($data)) return true;
        $model = self::getInstance();
        if (!$model->create($data)) {
            E(is_array($model->getError()) ? current($model->getError()) : $model->getError());
        }
        if (!$oldAddress = $model->where(['id' => intval($id), 'status' => self::STATUS_ACTIVE])->find()) {
            E('地址不存在');
        }
        if ($isDefault) {//如果当前这个被设为默认地址了，其他的都变成将就
            $model->where(['user_id' => $oldAddress['user_id']])->save(['default' => self::DEFAULT_FALSE]);
        }
        $sql = 'UPDATE sq_member_address_vehide SET ';
        foreach ($data as $field => $item) {
            $bindStr = ':' . $field;
            if ($field == $this->pk) continue;
            if ($this->fields['_type'][$field] === 'point') {
                $sql .= '`' . $field . '`=point(' . $item . '),';
            } else {
                $sql .= '`' . $field . '`=' . $bindStr . ',';
                $bind[$bindStr] = $item;
            }
        }
        $sql = rtrim($sql, ',') . ' WHERE id = :id';
        $bind[':id'] = intval($id);
        $pdo = get_pdo();
        $sth = $pdo->prepare($sql);
        $result = $sth->execute($bind);
        return $result;
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
        $sql = 'SELECT mav.user_id,mav.id,mav.address,mav.car_number,mav.status,mav.region_id,picture.path picture,mav.`default`,astext(mav.lnglat) lnglat,mav.street_number,mav.mobile FROM sq_member_address_vehide mav LEFT JOIN sq_picture picture ON mav.picture_id=picture.id WHERE mav.user_id = :user_id AND ';
        if ($status !== null && array_key_exists($status, self::getStatusOptions())) {
            $sql .= 'mav.status = :status';
            $bind[':status'] = intval($status);
        } else {
            $sql .= 'mav.status != :status';
            $bind[':status'] = self::STATUS_DELETE;
        }
        $sql .= ' ORDER BY mav.id DESC';
        $sth = $pdo->prepare($sql);
        $sth->execute($bind);
        $list = $sth->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($list as &$item) {
            if (empty($item['lnglat'])) {
                $item['lnglat'] = [
                    'lng' => 0,
                    'lat' => 0
                ];
                continue;
            }
            list($lng, $lat) = explode(' ', substr($item['lnglat'], 6, -1));
            $item['lnglat'] = [
                'lng' => $lng,
                'lat' => $lat
            ];
        }
        return $list;
    }

    /**
     * 删除地址
     * @author Fufeng Nie <niefueng@gmail.com>
     *
     * @param int $id ID
     * @param bool|true $logic 是否逻辑删除
     * @return bool|mixed
     */
    public function deleteById($id, $logic = true)
    {
        if (!$id = intval($id)) E('ID非法');
        if ($logic) return self::getInstance()->where(['id' => $id, 'status' => self::STATUS_ACTIVE])->save(['status' => self::STATUS_DELETE]);
        return self::getInstance()->delete($id);
    }
}
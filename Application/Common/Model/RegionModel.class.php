<?php

namespace Common\Model;

use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 * @author liuhui
 */
class RegionModel extends Model
{
    protected static $model;
    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_ACTIVE = 1;//正常

    protected $_validate = [
        [
            'status',
            [
                self::STATUS_DELETE,
                self::STATUS_ACTIVE
            ],
            '状态非法',
            self::MODEL_BOTH,
            'in'
        ]
    ];

    /**
     * 表有的字段
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @var array
     */
    protected $fields = [
        'id',
        'name',
        'pid',
        'level',
        'status',
        'lnglat',
        '_type' => [
            'id' => 'int',
            'name' => 'varchar',
            'pid' => 'smallint',
            'level' => 'tinyint',
            'status' => 'tinyint',
            'lnglat' => 'point',
        ]
    ];

    /**
     * 返回当前模型实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return RegionModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 获取区域信息 如果指定pid，返回所有pid等于pid的，不指定，则返回顶级
     * @param int $pid 父级ID
     * @param array $where
     * @return mixed
     */
    public function showChild($pid = 0, $where = [])
    {
        $map = ['status' => 1, 'pid' => $pid];
        $map = array_merge($map, $where);
        return $this->where($map)->select();
    }

    /**
     * 获取所有状态
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
     * 根据ID查找区域
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id ID
     * @param int $status 状态
     * @param string|array $fields 要查询的字段
     * @return array
     */
    public function getById($id, $status, $fields = '*')
    {
        $pdo = get_pdo();
        $fieldsString = '';
        switch (gettype($fields)) {
            case 'boolean':
                if ($fields === true) {//如果为真，就查询所有字段，否则就只查询名称
                    $fields = '*';
                } else {
                    $fields = 'name';
                }
            case 'string':
                $fields = trim($fields);
                if ($fields === '*') {
                    $fields = array_keys($this->fields);
                } else {
                    $fields = explode(',', $fields);
                }
            case 'array':
                foreach ($this->fields as $key => $field) {
                    if ($key === '_type' || !in_array($key, $fields)) continue;
                    if ($this->fields['_type'][$key] === 'point') {
                        $fieldsString .= 'AsText(' . $key . ') ' . $key . ',';
                    } else {
                        $fieldsString .= $key . ',';
                    }
                }
                break;
        }
        $sql = 'SELECT ' . $fields . ' FROM ' . self::getInstance()->tableName . ' WHERE id=:id AND status=:status';
        if ($status !== null && in_array($status, self::getStatusOptions())) {
            $bind[':status'] = $status;
        } else {
            $bind[':status'] = self::STATUS_ACTIVE;
        }
        $sth = $pdo->prepare($sql);
        $sth->execute([':id' => $id, ':status' => self::STATUS_ACTIVE]);
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 获得区域列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $pid 父级ID
     * @param null|int|array|string $level 要获取的分级，可传多个
     * @param null|int $status 状态
     * @param int|null $pageSize 分页大小，传NULL不分页（用于获取所有的数据来转树状结构）
     * @param string|array $fields 要查询的字段
     * @return array
     */
    public function getLists($pid = null, $level = null, $status = null, $pageSize = 20, $fields = '*')
    {
        $bind = [];
        $fieldsString = '';
        $nowPage = $_GET['p'] ? intval($_GET['p']) : 1;
        switch (gettype($fields)) {
            case 'boolean':
                if ($fields === true) {//如果为真，就查询所有字段，否则就只查询名称
                    $fields = '*';
                } else {
                    $fields = 'name';
                }
            case 'string':
                $fields = trim($fields);
                if ($fields === '*') {
                    $fields = array_values($this->fields);
                } else {
                    $fields = explode(',', $fields);
                }
            case 'array':
                foreach ($this->fields as $key => $field) {
                    if ($key === '_type' || !in_array($field, $fields)) continue;
                    if ($this->fields['_type'][$field] === 'point') {
                        $fieldsString .= 'astext(' . $field . ') ' . $field . ',';
                    } else {
                        $fieldsString .= $field . ',';
                    }
                }
                $fieldsString = rtrim($fieldsString, ',');
        }
        $where = ' status=:status';
        if ($status !== null || in_array($status, self::getStatusOptions())) {
            $bind[':status'] = $status;
        } else {
            $bind[':status'] = self::STATUS_ACTIVE;
        }
        if ($pid !== null) {
            $where .= ' AND pid=:pid';
            $bind[':pid'] = intval($pid);
        }
        if ($level !== null) {
            $level = array_unique(is_array($level) ? $level : explode(',', $level));
            $where .= ' AND level IN (';
            foreach ($level as $l) {
                $where .= intval($l) . ',';
            }
            $where = rtrim($where, ',') . ')';
        }
        $SelectSql = 'SELECT ' . $fieldsString . ' FROM ' . self::getInstance()->getTableName() . ' WHERE ' . $where;
        if ($pageSize) {
            $SelectSql . ' LIMIT ' . ($nowPage - 1) * $pageSize . ',' . $pageSize;
        }
        $sql = 'SELECT count(*) FROM ' . self::getInstance()->getTableName() . ' WHERE ' . $where;
        $pdo = get_pdo();
        $totalSth = $pdo->prepare($sql);
        $sth = $pdo->prepare($SelectSql);
        $totalSth->execute($bind);
        $sth->execute($bind);
        $lists = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (in_array('lnglat', $fields)) {
            foreach ($lists as &$list) {
                if ($list['lnglat']) {
                    $list['lnglat'] = explode(' ', substr($list['lnglat'], 6, -1));
                } else {
                    $list['lnglat'] = [];
                }
            }
        }
        return [
            'total' => (int)current($totalSth->fetch(\PDO::FETCH_ASSOC)),
            'data' => $lists,
        ];
    }

    /**
     * 获得区域树状结构数据
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $pid 父级ID
     * @param null|int|string|array $level 需要获取的等级，可传多个
     * @param null|int $status 状态
     * @param null|int $pageSize 分页大小
     * @param string|array $fields 要查询的字段
     * @return array
     */
    public function getTree($pid = 0, $level = null, $status = null, $pageSize = null, $lng = null, $lat = null, $fields = 'id,name,pid,level')
    {
        $level = $level === null ? [0, 1, 2, 3, 4, 5] : $level;
        $level = array_unique(is_array($level) ?: explode(',', $level));
        asort($level);
        $nowPage = isset($_GET['p']) ? $_GET['p'] : 1;
        $cacheKey = 'data_region_tree_level' . implode('', $level);
        if (!$tree = S($cacheKey)) {
            $lists = self::getInstance()->getLists(null, $level, $status, $pageSize, $fields);
            $tree = list_to_tree($lists['data'], 'id', 'pid', '_childs', $pid);
            S($cacheKey, $tree, 86400);
        }
        //因为最后一层需要按距离排序
        if (in_array(5, $level) && $lng && $lat) {
            $communitys = $this->getNearbyCommunityByLnglat($lng, $lat);
            $this->addRegionToTree($tree, $communitys, array_unique(array_column($communitys, 'pid')), array_column($communitys, 'id'));
        }

        return [
            'data' => $tree
        ];
    }

    /**
     * 往树里添加区域
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param arrary $tree 树状结构
     * @param array $regions 需要添加进去的区域数组
     * @param array $pids 所有的父级ID列表，在外面传可以避免递归的时候每次计算
     * @param array $addIds 所有要添加进去的ID列表，在外面传可以避免递归的时候每次计算
     */
    protected static function addRegionToTree(&$tree, $regions, $pids, $addIds)
    {
        foreach ($tree as &$item) {
            if (!empty($item['_childs'])) {
                self::addRegionToTree($item['_childs'], $regions, $pids, $addIds);
            }
            if ($item['level'] == 4 && in_array($item['id'], $pids)) {
                $hasIds = array_column($item['_childs'], 'id');
                $ids = array_diff($hasIds, $addIds);
                foreach ($regions as $region) {
                    if (!in_array($regions['id'], $ids)) {
                        array_unshift($item['_childs'], $region);
                    }
                }

            }
        }
    }

    /**
     * 获得附近xx米之内的小区
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param int $distance 距离
     * @return array
     */
    protected static function getNearbyCommunityByLnglat($lng, $lat, $distance = 2000)
    {
        return self::getInstance()->where([
            'status' => self::STATUS_ACTIVE,
            'level' => 5,
            'ST_Distance_Sphere(lnglat,point(' . $lng . ',' . $lat . ')) <= ' . $distance
        ])->field(['id', 'name'])->select();
    }
}


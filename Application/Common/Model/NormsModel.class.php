<?php
namespace Common\Model;

use Think\Model\RelationModel;

/**
 * 规格模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @property int $id
 * @property string $title 标题
 * @property int $status 状态
 *
 * @package Common\Model
 */
class NormsModel extends RelationModel
{
    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_CLOSE = 0;//关闭
    const STATUS_ACTIVE = 1;//正常

    protected $autoinc = true;
    protected $pk = 'id';
    /**
     * @var NormsModel
     */
    protected static $model;

    /**
     * 获取当前模型实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return NormsModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 获取所有状态选项
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '已删除',
            self::STATUS_CLOSE => '已关闭',
            self::STATUS_ACTIVE => '正常'
        ];
    }

    protected $fields = [
        'id',
        'title',
        'status',
        '_type' => [
            'id' => 'int',
            'title' => 'varchar',
            'status' => 'tinyint'
        ]
    ];

    /**
     * 获取规格列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|string|array $categoryIds 分类ID
     * @param null|string|array $brandIds 品牌ID
     * @param null|int $status 状态
     * @param int|null $pageSize 分页大小
     * @return array
     */
    public function getLists($categoryIds = null, $brandIds = null, $status = null, $pageSize = 20)
    {
        $nowPage = empty($_GET['p']) ? 1 : intval($_GET['p']);
        if ($status === null && !array_key_exists($status, self::getStatusOptions())) {
            $status = self::STATUS_ACTIVE;
        }
        $subWhere = '';
        if (!empty($categoryIds)) {//如果分类ID不为空，则在category_brand_norms表查询
            $categoryIds = array_unique(is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds));
            $subWhere .= ' AND cbn.category_id IN (';
            foreach ($categoryIds as $category) {
                $subWhere .= intval($category) . ',';
            }
            $subWhere = rtrim($subWhere, ',') . ')';
        }
        if (!empty($brandIds)) {//如果品牌ID不为空，则在category_brand_norms表查询
            $brandIds = array_unique(is_array($brandIds) ? $brandIds : explode(',', $brandIds));
            $subWhere .= ' AND cbn.brand_id IN (';
            foreach ($brandIds as $brand) {
                $subWhere .= intval($brand) . ',';
            }
            $subWhere = rtrim($subWhere, ',') . ')';
        }
        if (!empty($categoryIds) || !empty($brandIds)) {//如果分类ID或者品牌ID不为空
            //子查询
            $subWhere = ltrim($subWhere, ' AND');
            $subSql = 'SELECT norms_id FROM ' . C('DB_PREFIX') . 'category_brand_norms' . ' cbn WHERE ' . $subWhere;
            $sql = 'SELECT * FROM ' . self::getInstance()->getTableName() . ' WHERE id IN (' . $subSql . ') AND status=' . $status.' GROUP BY id';
        } else {//如果两个都为空
            $sql = 'SELECT * FROM ' . self::getInstance()->getTableName() . ' WHERE status=' . $status.' GROUP BY id';
        }
        if ($pageSize) {
            $pageSize = intval($pageSize);
            $sql .= ' LIMIT ' . ($nowPage - 1) * $pageSize . ',' . $pageSize;
        }
        $pdo = get_pdo();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        return [
            'data' => $sth->fetchAll(\PDO::FETCH_ASSOC)
        ];
    }
}
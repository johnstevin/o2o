<?php
namespace Common\Model;

use Think\Model\RelationModel;

/**
 * 品牌模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @property int $id ID
 * @property string $title 标题
 * @property int $status 状态
 * @property int $sort 排序
 * @property string $description 介绍
 *
 * @package Common\Model
 */
class BrandModel extends RelationModel
{
    ## 状态常量
    const STATUS_DELETE = -1;//逻辑删除
    const STATUS_CLOSE = 0;//关闭
    const STATUS_ACTIVE = 1;//正常

    protected static $model;
    protected $autoinc = true;
    protected $pk = 'id';

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return BrandModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    protected $fields = [
        'id',
        'title',
        'status',
        'logo',
        'sort',
        'description',
        '_type' => [
            'id' => 'int',
            'title' => 'varchar',
            'status' => 'tinyint',
            'logo' => 'int',
            'sort' => 'tinyint',
            'description' => 'varchar'
        ]
    ];

    /**
     * 获取品牌列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int|array|string $categoryIds 分类的ID，可传多个
     * @param null|int $pageSize 分页大小
     * @return array
     */
    public function getLists($categoryIds = null, $pageSize = null)
    {
        $where = [
            'status' => self::STATUS_ACTIVE
        ];
        if ($categoryIds !== null) {
            $categoryIds = array_unique(is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds));
            $ids = M('CategoryBrandNorms')->where(['category_id' => ['IN', $categoryIds]])->field('brand_id')->group('brand_id')->select();
            if (empty($ids)) return [
                'data' => []
            ];
            $where['id'] = [
                'IN',
                array_column($ids, 'brand_id')
            ];
        }

        $model = self::getInstance();
        if ($pageSize !== null) {
            $model->page(isset($_GET['p']) ? $_GET['p'] : 1, $pageSize);
        }
        return [
            'data' => $model->where($where)->select()
        ];
    }
}
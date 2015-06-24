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

    public function getLists(){}
}